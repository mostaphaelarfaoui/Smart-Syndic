<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. Super Admin Only ───────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@smartsyndic.com'],
            [
                'tenant_id'        => null,
                'role'             => 'SUPER_ADMIN',
                'first_name'       => 'Platform',
                'last_name'        => 'Administrator',
                'phone'            => '+212600000000',
                'password'         => Hash::make('password'),
                'locale'           => 'ar',
                'theme_preference' => 'system',
                'status'           => 'ACTIVE',
            ]
        );

        $this->command->info('🏢 Smart Syndic database initialized clean with Super Admin only.');
    }
}
