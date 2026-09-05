<?php

namespace App\Services\Mail;

use App\Mail\TestEmailMailable;
use App\Mail\TransactionReceiptMailable;
use App\Mail\WelcomeCustomerMailable;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailGatewayService
{
    /**
     * Apply runtime mail configuration from database settings.
     */
    public function applyConfiguration(): void
    {
        $mailer = Setting::get('mail_mailer', config('mail.default', 'smtp'));
        $host = Setting::get('mail_host', config('mail.mailers.smtp.host', '127.0.0.1'));
        $port = (int) Setting::get('mail_port', config('mail.mailers.smtp.port', 587));
        $username = Setting::get('mail_username', config('mail.mailers.smtp.username'));
        $password = Setting::get('mail_password', config('mail.mailers.smtp.password'));
        $encryption = Setting::get('mail_encryption', 'tls');
        $fromAddress = Setting::get('mail_from_address', config('mail.from.address', 'noreply@kreatifabadi.co.id'));
        $fromName = Setting::get('mail_from_name', config('mail.from.name', 'PT Kreatif Abadi QRIS'));

        config([
            'mail.default' => $mailer,
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $username,
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.encryption' => ($encryption === 'none' || empty($encryption)) ? null : $encryption,
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $fromName,
        ]);

        // Purge resolved mailer instances so new configuration is picked up
        Mail::purge();
    }

    /**
     * Get sanitized gateway configuration for UI display.
     */
    public function getConfig(): array
    {
        return [
            'mailer' => Setting::get('mail_mailer', config('mail.default', 'smtp')),
            'host' => Setting::get('mail_host', config('mail.mailers.smtp.host', '127.0.0.1')),
            'port' => (int) Setting::get('mail_port', config('mail.mailers.smtp.port', 587)),
            'username' => Setting::get('mail_username', config('mail.mailers.smtp.username', '')),
            'password_set' => !empty(Setting::get('mail_password', config('mail.mailers.smtp.password'))),
            'encryption' => Setting::get('mail_encryption', 'tls'),
            'from_address' => Setting::get('mail_from_address', config('mail.from.address', 'noreply@kreatifabadi.co.id')),
            'from_name' => Setting::get('mail_from_name', config('mail.from.name', 'PT Kreatif Abadi QRIS')),
            'is_active' => (bool) Setting::get('mail_gateway_active', true),
        ];
    }

    /**
     * Update mail gateway configuration in database.
     */
    public function updateConfig(array $data): void
    {
        if (isset($data['mailer'])) {
            Setting::set('mail_mailer', $data['mailer'], 'mail', 'string', 'Driver pengiriman email (smtp, sendmail, log)');
        }
        if (isset($data['host'])) {
            Setting::set('mail_host', $data['host'], 'mail', 'string', 'Hostname SMTP server');
        }
        if (isset($data['port'])) {
            Setting::set('mail_port', (int) $data['port'], 'mail', 'integer', 'Port SMTP server');
        }
        if (isset($data['username'])) {
            Setting::set('mail_username', $data['username'], 'mail', 'string', 'Username SMTP');
        }
        if (!empty($data['password'])) {
            Setting::set('mail_password', $data['password'], 'mail', 'string', 'Password SMTP');
        }
        if (isset($data['encryption'])) {
            Setting::set('mail_encryption', $data['encryption'], 'mail', 'string', 'Enkripsi SMTP (tls, ssl, none)');
        }
        if (isset($data['from_address'])) {
            Setting::set('mail_from_address', $data['from_address'], 'mail', 'string', 'Email pengirim');
        }
        if (isset($data['from_name'])) {
            Setting::set('mail_from_name', $data['from_name'], 'mail', 'string', 'Nama pengirim');
        }
        if (isset($data['is_active'])) {
            Setting::set('mail_gateway_active', (bool) $data['is_active'], 'mail', 'boolean', 'Status aktif email gateway');
        }

        $this->applyConfiguration();
    }

    /**
     * Send test verification email to confirm SMTP connectivity.
     */
    public function sendTestEmail(string $recipientEmail): array
    {
        $this->applyConfiguration();

        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host', '127.0.0.1');
        $start = microtime(true);

        try {
            Mail::to($recipientEmail)->send(new TestEmailMailable($recipientEmail, $mailer, $host));
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'success' => true,
                'message' => "Email pengujian berhasil dikirim ke {$recipientEmail} ({$latency} ms).",
                'latency_ms' => $latency,
                'mailer' => $mailer,
                'host' => $host,
            ];
        } catch (\Throwable $e) {
            Log::error("Email Gateway test failed: " . $e->getMessage(), ['exception' => $e]);

            return [
                'success' => false,
                'message' => 'Gagal mengirim email: ' . $e->getMessage(),
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                'mailer' => $mailer,
                'host' => $host,
            ];
        }
    }

    /**
     * Send welcome email to new customer.
     */
    public function sendWelcomeEmail(User $user, Customer $customer): bool
    {
        if (!Setting::get('mail_gateway_active', true)) {
            return false;
        }

        try {
            $this->applyConfiguration();
            Mail::to($user->email)->queue(new WelcomeCustomerMailable($user, $customer));
            return true;
        } catch (\Throwable $e) {
            Log::warning("Could not send welcome email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send transaction receipt email.
     */
    public function sendTransactionPaidEmail(Transaction $transaction): bool
    {
        if (!Setting::get('mail_gateway_active', true)) {
            return false;
        }

        $customerEmail = $transaction->customer?->email ?? $transaction->customer?->users()->first()?->email;
        if (!$customerEmail) {
            return false;
        }

        try {
            $this->applyConfiguration();
            Mail::to($customerEmail)->queue(new TransactionReceiptMailable($transaction));
            return true;
        } catch (\Throwable $e) {
            Log::warning("Could not send transaction receipt email: {$e->getMessage()}");
            return false;
        }
    }
}
