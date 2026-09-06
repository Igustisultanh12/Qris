<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['group' => 'general', 'key' => 'app_name', 'value' => 'Qmis', 'type' => 'string', 'description' => 'Nama platform aplikasi'],
            ['group' => 'general', 'key' => 'company_name', 'value' => 'PT Kreatif Sky Abadi', 'type' => 'string', 'description' => 'Nama legal perusahaan'],
            ['group' => 'general', 'key' => 'company_email', 'value' => 'support@kreatifskyabadi.co.id', 'type' => 'string', 'description' => 'Email kontak resmi'],
            ['group' => 'general', 'key' => 'company_phone', 'value' => '+62 21 555 0199', 'type' => 'string', 'description' => 'Nomor telepon kantor'],
            ['group' => 'general', 'key' => 'company_address', 'value' => 'Jl. Sudirman No. 88, Jakarta Pusat, DKI Jakarta 10220', 'type' => 'string', 'description' => 'Alamat kantor operasional'],

            // QRIS Engine & Platform Static QRIS
            ['group' => 'qris', 'key' => 'qris_default_expiry_minutes', 'value' => '15', 'type' => 'integer', 'description' => 'Masa berlaku default dynamic QR (menit)'],
            ['group' => 'qris', 'key' => 'qris_min_amount', 'value' => '1000', 'type' => 'integer', 'description' => 'Nominal transaksi minimum QRIS (Rp)'],
            ['group' => 'qris', 'key' => 'qris_max_amount', 'value' => '100000000', 'type' => 'integer', 'description' => 'Nominal transaksi maksimum QRIS (Rp)'],
            ['group' => 'qris', 'key' => 'qris_default_fee_mode', 'value' => 'charged_to_customer', 'type' => 'string', 'description' => 'Mode penagihan fee default'],
            ['group' => 'qris', 'key' => 'platform_qris_static', 'value' => '00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000010303UMI5204581253033605802ID5920PT KREATIF SKY ABADI6007JAKARTA61051011062070703A016304B835', 'type' => 'string', 'description' => 'Payload string QRIS Statis platform untuk pembayaran langganan'],

            ['group' => 'qris', 'key' => 'platform_qris_merchant_name', 'value' => 'PT KREATIF SKY ABADI', 'type' => 'string', 'description' => 'Nama merchant resmi pada QRIS platform'],
            ['group' => 'qris', 'key' => 'platform_qris_merchant_city', 'value' => 'JAKARTA', 'type' => 'string', 'description' => 'Kota merchant pada QRIS platform'],
            ['group' => 'qris', 'key' => 'platform_qris_postal_code', 'value' => '10110', 'type' => 'string', 'description' => 'Kode pos merchant platform'],
            ['group' => 'qris', 'key' => 'platform_qris_enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Status aktif pembayaran langganan menggunakan QRIS dinamis'],


            // Billing & Taxes
            ['group' => 'billing', 'key' => 'billing_tax_enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Aktifkan pajak PPN pada invoice'],
            ['group' => 'billing', 'key' => 'billing_tax_percent', 'value' => '11.00', 'type' => 'decimal', 'description' => 'Persentase PPN (%)'],
            ['group' => 'billing', 'key' => 'invoice_prefix', 'value' => 'INV', 'type' => 'string', 'description' => 'Prefix nomor invoice'],
            ['group' => 'billing', 'key' => 'invoice_due_days', 'value' => '3', 'type' => 'integer', 'description' => 'Jatuh tempo invoice (hari)'],
            ['group' => 'billing', 'key' => 'subscription_grace_period_days', 'value' => '3', 'type' => 'integer', 'description' => 'Masa tenggang subscription expired (hari)'],
            ['group' => 'billing', 'key' => 'billing_payment_gateway', 'value' => 'manual', 'type' => 'string', 'description' => 'Driver payment gateway aktif (manual, midtrans, xendit, tripay)'],

            // API Configuration
            ['group' => 'api', 'key' => 'api_default_rate_limit', 'value' => '60', 'type' => 'integer', 'description' => 'Default rate limit per menit'],
            ['group' => 'api', 'key' => 'api_webhook_max_retries', 'value' => '3', 'type' => 'integer', 'description' => 'Maksimum retry pengiriman webhook'],
            ['group' => 'api', 'key' => 'api_webhook_timeout_seconds', 'value' => '10', 'type' => 'integer', 'description' => 'Timeout HTTP webhook (detik)'],

            // Security
            ['group' => 'security', 'key' => 'security_max_login_attempts', 'value' => '5', 'type' => 'integer', 'description' => 'Batas percobaan login sebelum lockout'],
            ['group' => 'security', 'key' => 'security_session_lifetime_minutes', 'value' => '120', 'type' => 'integer', 'description' => 'Masa aktif session login (menit)'],
            ['group' => 'security', 'key' => 'security_maintenance_mode', 'value' => '0', 'type' => 'boolean', 'description' => 'Status mode pemeliharaan sistem'],

            // Email Gateway
            ['group' => 'mail', 'key' => 'mail_mailer', 'value' => 'smtp', 'type' => 'string', 'description' => 'Driver mailer aktif (smtp, sendmail, log)'],
            ['group' => 'mail', 'key' => 'mail_host', 'value' => 'smtp.mailtrap.io', 'type' => 'string', 'description' => 'Hostname SMTP server'],
            ['group' => 'mail', 'key' => 'mail_port', 'value' => '587', 'type' => 'integer', 'description' => 'Port SMTP server'],
            ['group' => 'mail', 'key' => 'mail_username', 'value' => 'postmaster@kreatifskyabadi.co.id', 'type' => 'string', 'description' => 'Username SMTP autentikasi'],
            ['group' => 'mail', 'key' => 'mail_password', 'value' => '', 'type' => 'string', 'description' => 'Password SMTP autentikasi'],
            ['group' => 'mail', 'key' => 'mail_encryption', 'value' => 'tls', 'type' => 'string', 'description' => 'Enkripsi protokol (tls, ssl, none)'],
            ['group' => 'mail', 'key' => 'mail_from_address', 'value' => 'noreply@kreatifskyabadi.co.id', 'type' => 'string', 'description' => 'Alamat email pengirim'],
            ['group' => 'mail', 'key' => 'mail_from_name', 'value' => 'Qmis - PT Kreatif Sky Abadi', 'type' => 'string', 'description' => 'Nama pengirim notifikasi'],
            ['group' => 'mail', 'key' => 'mail_gateway_active', 'value' => '1', 'type' => 'boolean', 'description' => 'Status aktif pengiriman email transaksi'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
