<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'basic',
                'name' => 'BASIC',
                'description' => 'Cocok untuk usaha perorangan, warung, atau toko retail kecil yang baru mulai digitalisasi pembayaran.',
                'price' => 25000,
                'billing_cycle' => 'monthly',
                'max_merchants' => 3,
                'max_api_calls_per_month' => 5000,
                'max_transactions_per_month' => 2000,
                'max_storage_mb' => 500,
                'rate_limit_per_minute' => 60,
                'features' => [
                    'api_access' => true,
                    'webhook' => true,
                    'camera_scanner' => true,
                    'reports' => true,
                    'multiple_merchants' => false,
                    'export' => false,
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ],
            [
                'slug' => 'pro',
                'name' => 'PRO',
                'description' => 'Solusi ideal untuk bisnis berkembang, cafe, restoran, dan toko online dengan integrasi POS.',
                'price' => 50000,
                'billing_cycle' => 'monthly',
                'max_merchants' => 10,
                'max_api_calls_per_month' => 25000,
                'max_transactions_per_month' => 10000,
                'max_storage_mb' => 2000,
                'rate_limit_per_minute' => 120,
                'features' => [
                    'api_access' => true,
                    'webhook' => true,
                    'camera_scanner' => true,
                    'reports' => true,
                    'multiple_merchants' => true,
                    'export' => true,
                ],
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'business',
                'name' => 'BUSINESS',
                'description' => 'Untuk korporasi, waralaba / cabang banyak, startup fintech, dan platform e-commerce volume tinggi.',
                'price' => 100000,
                'billing_cycle' => 'monthly',
                'max_merchants' => 50,
                'max_api_calls_per_month' => 100000,
                'max_transactions_per_month' => 50000,
                'max_storage_mb' => 10000,
                'rate_limit_per_minute' => 300,
                'features' => [
                    'api_access' => true,
                    'webhook' => true,
                    'camera_scanner' => true,
                    'reports' => true,
                    'multiple_merchants' => true,
                    'export' => true,
                    'dedicated_support' => true,
                ],
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $p) {
            SubscriptionPlan::updateOrCreate(['slug' => $p['slug']], $p);
        }
    }
}
