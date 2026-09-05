<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiredCount = 0;

        Transaction::whereIn('status', ['pending', 'generated'])
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($transactions) use (&$expiredCount) {
                foreach ($transactions as $transaction) {
                    $transaction->update(['status' => 'expired']);
                    $expiredCount++;

                    // Dispatch webhook for expired transaction
                    if ($transaction->customer) {
                        DispatchWebhookJob::dispatch($transaction->customer, 'transaction.expired', [
                            'transaction_id' => $transaction->transaction_number,
                            'uuid' => $transaction->uuid,
                            'reference' => $transaction->reference,
                            'status' => 'expired',
                            'expired_at' => now()->toIso8601String(),
                        ]);
                    }
                }
            });

        if ($expiredCount > 0) {
            Log::info("ExpireTransactionsJob: Expired {$expiredCount} transactions.");
        }
    }
}
