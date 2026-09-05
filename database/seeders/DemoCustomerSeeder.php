<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerProfile;
use App\Models\Merchant;
use App\Models\MerchantQris;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\TransactionFee;
use App\Models\User;
use App\Models\Webhook;
use App\Services\Qris\Crc16;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Customer Organization
        $customer = Customer::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Budi Santoso',
                'email' => 'demo@example.com',
                'phone' => '+6281399887766',
                'business_name' => 'PT Aneka Sukses Bersama',
                'business_type' => 'Retail & F&B',
                'tax_number' => '01.234.567.8-901.000',
                'address' => 'Gedung Wisma Niaga Lt. 5, Jl. Jendral Sudirman Kav. 21',
                'city' => 'Jakarta Selatan',
                'province' => 'DKI Jakarta',
                'postal_code' => '12190',
                'status' => 'active',
                'max_merchants' => 10,
            ]
        );

        CustomerProfile::updateOrCreate(
            ['customer_id' => $customer->id],
            [
                'website' => 'https://anekasukses.co.id',
                'webhook_url' => 'https://webhook.site/demo-kreatif-qris',
                'webhook_secret' => 'whsec_' . Str::random(32),
                'notification_preferences' => ['email' => true, 'webhook' => true],
            ]
        );

        // 2. Create User Account for Customer
        $user = User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'customer_id' => $customer->id,
                'name' => 'Budi Santoso (Demo User)',
                'email' => 'demo@example.com',
                'phone' => '+6281399887766',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        $customerRole = Role::where('slug', 'customer')->first();
        if ($customerRole) {
            $user->roles()->syncWithoutDetaching([$customerRole->id]);
        }

        // 3. Assign Pro Subscription
        $proPlan = SubscriptionPlan::where('slug', 'pro')->first();
        if ($proPlan) {
            Subscription::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'plan_id' => $proPlan->id,
                    'status' => 'active',
                    'price' => $proPlan->price,
                    'currency' => 'IDR',
                    'starts_at' => now()->subDays(5),
                    'ends_at' => now()->addDays(25),
                    'auto_renew' => true,
                    'grace_period_days' => 3,
                ]
            );
        }

        // 4. Create Demo Merchants with valid QRIS Static Payloads
        $qrisStatic1Base = '00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000010303UMI5204541153033605802ID5923TOKO KREATIF MART PUSAT6013JAKARTA PUSAT61051011062070703A016304';
        $qrisStatic1 = $qrisStatic1Base . Crc16::calculate($qrisStatic1Base);

        $merchant1 = Merchant::updateOrCreate(
            ['merchant_code' => 'MC-KREATIF-001'],
            [
                'customer_id' => $customer->id,
                'name' => 'Toko Kreatif Mart Pusat',
                'store_name' => 'Kreatif Mart Sudirman',
                'address' => 'Jl. Sudirman No. 12',
                'city' => 'Jakarta Pusat',
                'postal_code' => '10110',
                'mcc' => '5411',
                'acquirer_name' => 'LinkAja / Nobu',
                'status' => 'active',
                'fee_mode' => 'charged_to_customer',
                'custom_fee_type' => 'fixed',
                'custom_fee_value' => 1000,
            ]
        );

        MerchantQris::updateOrCreate(
            ['merchant_id' => $merchant1->id],
            [
                'customer_id' => $customer->id,
                'qris_static' => $qrisStatic1,
                'qris_version' => '01',
                'merchant_name_qris' => 'TOKO KREATIF MART PUSAT',
                'merchant_city_qris' => 'JAKARTA PUSAT',
                'postal_code' => '10110',
                'currency' => '360',
                'mcc' => '5411',
                'is_primary' => true,
                'is_active' => true,
            ]
        );

        $qrisStatic2Base = '00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000020303UMI51440014ID.CO.QRIS.WWW0215ID10200210000020303UMI5204581253033605802ID5926KREATIF COFFEE & ROASTERY6007BANDUNG61054011562070703B026304';
        $qrisStatic2 = $qrisStatic2Base . Crc16::calculate($qrisStatic2Base);

        $merchant2 = Merchant::updateOrCreate(
            ['merchant_code' => 'MC-COFFEE-002'],
            [
                'customer_id' => $customer->id,
                'name' => 'Kreatif Coffee & Roastery',
                'store_name' => 'Kreatif Coffee Dago',
                'address' => 'Jl. Ir. H. Juanda No. 102',
                'city' => 'Bandung',
                'postal_code' => '40115',
                'mcc' => '5812',
                'acquirer_name' => 'BCA / QRIS',
                'status' => 'active',
                'fee_mode' => 'absorbed',
                'custom_fee_type' => 'percentage',
                'custom_fee_value' => 0.70,
            ]
        );

        MerchantQris::updateOrCreate(
            ['merchant_id' => $merchant2->id],
            [
                'customer_id' => $customer->id,
                'qris_static' => $qrisStatic2,
                'qris_version' => '01',
                'merchant_name_qris' => 'KREATIF COFFEE & ROASTERY',
                'merchant_city_qris' => 'BANDUNG',
                'postal_code' => '40115',
                'currency' => '360',
                'mcc' => '5812',
                'is_primary' => true,
                'is_active' => true,
            ]
        );

        // 5. Create Deterministic Demo API Key
        $knownKey = 'ka_live_demo1234567890abcdef12';
        $knownSecret = 'kas_demoSecretKey9876543210zyxwvutsrq';
        ApiKey::updateOrCreate(
            ['key_hash' => hash('sha256', $knownKey)],
            [
                'customer_id' => $customer->id,
                'name' => 'Demo POS Integration Key',
                'key_prefix' => substr($knownKey, 0, 16) . '...',
                'secret_hash' => hash('sha256', $knownSecret),
                'rate_limit_per_minute' => 120,
                'is_active' => true,
            ]
        );

        // 6. Create Demo Webhook
        Webhook::updateOrCreate(
            ['customer_id' => $customer->id, 'url' => 'https://webhook.site/demo-kreatif-qris'],
            [
                'secret' => 'whsec_kreatifdemo2026',
                'events' => ['transaction.generated', 'transaction.paid', 'transaction.expired', 'invoice.paid'],
                'is_active' => true,
            ]
        );

        // 7. Create Demo Transactions
        $tx1 = Transaction::updateOrCreate(
            ['reference' => 'ORDER-DEMO-001'],
            [
                'customer_id' => $customer->id,
                'merchant_id' => $merchant1->id,
                'amount' => 75000,
                'fee' => 1000,
                'total' => 76000,
                'fee_mode' => 'charged_to_customer',
                'qris_static' => $qrisStatic1,
                'qris_dynamic' => $qrisStatic1, // placeholder
                'status' => 'generated',
                'source' => 'api',
                'expires_at' => now()->addMinutes(15),
            ]
        );

        TransactionFee::updateOrCreate(
            ['transaction_id' => $tx1->id],
            [
                'fee_type' => 'fixed',
                'fee_rate' => 0,
                'fee_amount' => 1000,
                'fee_mode' => 'charged_to_customer',
                'platform_cut' => 1000,
                'merchant_net' => 75000,
            ]
        );

        // 8. Create Demo Promo Coupon
        Coupon::updateOrCreate(
            ['code' => 'KREATIF10'],
            [
                'name' => 'Diskon Pelanggan Baru 10%',
                'type' => 'percentage',
                'value' => 10,
                'max_discount' => 20000,
                'min_spend' => 25000,
                'max_uses' => 1000,
                'max_uses_per_customer' => 1,
                'starts_at' => now()->subMonth(),
                'ends_at' => now()->addMonths(12),
                'is_active' => true,
            ]
        );
    }
}
