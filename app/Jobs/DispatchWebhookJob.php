<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Customer $customer,
        public string $event,
        public array $data,
        public ?WebhookDelivery $delivery = null
    ) {}

    public function backoff(): array
    {
        // Exponential backoff: 30s, 120s, 300s
        return [30, 120, 300];
    }

    public function handle(): void
    {
        $webhooks = $this->customer->webhooks()->where('is_active', true)->get();

        foreach ($webhooks as $webhook) {
            // Check if webhook subscribes to this event
            $subscribedEvents = $webhook->events ?: ['*'];
            if (!in_array('*', $subscribedEvents, true) && !in_array($this->event, $subscribedEvents, true)) {
                continue;
            }

            $payload = [
                'event' => $this->event,
                'timestamp' => now()->toIso8601String(),
                'data' => $this->data,
            ];
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $signature = hash_hmac('sha256', $jsonPayload, $webhook->secret);
            $deliveryUuid = $this->delivery?->uuid ?: (string) Str::uuid();

            $delivery = $this->delivery ?: WebhookDelivery::create([
                'uuid' => $deliveryUuid,
                'webhook_id' => $webhook->id,
                'customer_id' => $this->customer->id,
                'event' => $this->event,
                'payload' => $payload,
                'attempt' => 1,
                'max_attempts' => $this->tries,
                'is_success' => false,
            ]);

            $startTime = microtime(true);

            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'Qmis-Webhook/1.0',
                        'X-Qmis-Signature' => $signature,
                        'X-Signature-SHA256' => $signature,
                        'X-Qmis-Event' => $this->event,
                        'X-Qmis-Delivery' => $deliveryUuid,
                    ])
                    ->post($webhook->url, $payload);

                $durationMs = (int) round((microtime(true) - $startTime) * 1000);
                $isSuccess = $response->successful();

                $delivery->update([
                    'response_status' => $response->status(),
                    'response_headers' => $response->headers(),
                    'response_body' => substr($response->body(), 0, 2000),
                    'duration_ms' => $durationMs,
                    'is_success' => $isSuccess,
                    'attempt' => $this->attempts(),
                    'error_message' => $isSuccess ? null : "HTTP Error {$response->status()}",
                ]);

                if ($isSuccess) {
                    $webhook->update([
                        'failure_count' => 0,
                        'last_triggered_at' => now(),
                    ]);
                } else {
                    $webhook->increment('failure_count');
                    if ($this->attempts() < $this->tries) {
                        $this->release($this->backoff()[$this->attempts() - 1] ?? 60);
                    }
                }
            } catch (\Throwable $e) {
                $durationMs = (int) round((microtime(true) - $startTime) * 1000);

                $delivery->update([
                    'response_status' => 0,
                    'duration_ms' => $durationMs,
                    'is_success' => false,
                    'attempt' => $this->attempts(),
                    'error_message' => substr($e->getMessage(), 0, 1000),
                ]);

                $webhook->increment('failure_count');
                Log::warning("Webhook delivery failed: {$e->getMessage()}", ['webhook_id' => $webhook->id]);

                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff()[$this->attempts() - 1] ?? 60);
                }
            }
        }
    }
}
