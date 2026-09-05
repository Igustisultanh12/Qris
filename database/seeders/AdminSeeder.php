<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@kreatifskyabadi.co.id');
        $password = env('ADMIN_PASSWORD', 'KreatifSkyAbadi2026!');

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Administrator PT Kreatif Sky Abadi',
                'email' => $email,
                'phone' => '+6281234567890',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }
    }
}
