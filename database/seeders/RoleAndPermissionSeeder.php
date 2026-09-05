<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Administrator',
            'description' => 'Full access to platform administration, tenants, finance, settings, and audit logs.',
        ]);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], [
            'name' => 'Administrator',
            'description' => 'Operational access to monitor merchants, transactions, and support tickets.',
        ]);

        $customerRole = Role::firstOrCreate(['slug' => 'customer'], [
            'name' => 'Customer / Tenant',
            'description' => 'Business owner managing merchants, QRIS generation, API keys, and billing.',
        ]);

        $permissions = [
            // Dashboard
            ['name' => 'View Admin Dashboard', 'slug' => 'admin.dashboard', 'group' => 'admin'],
            ['name' => 'View Customer Dashboard', 'slug' => 'customer.dashboard', 'group' => 'customer'],

            // Merchants
            ['name' => 'Manage Merchants', 'slug' => 'merchants.manage', 'group' => 'merchants'],
            ['name' => 'View All Merchants', 'slug' => 'merchants.view_all', 'group' => 'merchants'],

            // QRIS & Transactions
            ['name' => 'Generate Dynamic QRIS', 'slug' => 'qris.generate', 'group' => 'qris'],
            ['name' => 'View Transactions', 'slug' => 'transactions.view', 'group' => 'transactions'],
            ['name' => 'View All Transactions', 'slug' => 'transactions.view_all', 'group' => 'transactions'],

            // API Keys & Webhooks
            ['name' => 'Manage API Keys', 'slug' => 'api_keys.manage', 'group' => 'api'],
            ['name' => 'Manage Webhooks', 'slug' => 'webhooks.manage', 'group' => 'webhooks'],

            // Billing & Plans
            ['name' => 'View Invoices', 'slug' => 'invoices.view', 'group' => 'billing'],
            ['name' => 'Manage Plans', 'slug' => 'plans.manage', 'group' => 'billing'],
            ['name' => 'Manage Refunds', 'slug' => 'refunds.manage', 'group' => 'billing'],

            // System & Audit
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'system'],
            ['name' => 'View Audit Logs', 'slug' => 'audit_logs.view', 'group' => 'system'],
        ];

        foreach ($permissions as $p) {
            $perm = Permission::firstOrCreate(['slug' => $p['slug']], $p);
            $superAdminRole->permissions()->syncWithoutDetaching([$perm->id]);

            if ($p['group'] === 'customer') {
                $customerRole->permissions()->syncWithoutDetaching([$perm->id]);
            }
        }
    }
}
