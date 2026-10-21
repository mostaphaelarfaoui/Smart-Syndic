<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. Super Admin ───────────────────────────────────────────
        User::create([
            'tenant_id'        => null,
            'role'             => 'SUPER_ADMIN',
            'first_name'       => 'Platform',
            'last_name'        => 'Administrator',
            'email'            => 'admin@smartsyndic.com',
            'phone'            => '+212600000000',
            'password'         => Hash::make('password'),
            'locale'           => 'fr',
            'theme_preference' => 'dark',
            'status'           => 'ACTIVE',
        ]);

        // ─── 2. Tenant 1: إقامة سكنية (عمارة منفردة) ─────────────────
        $residence = Tenant::create([
            'name'                     => 'Résidence Al Firdaws',
            'property_type'            => 'SINGLE_BUILDING',
            'plan_type'                => 'PRO',
            'status'                   => 'ACTIVE',
            'trial_ends_at'            => now()->addMonths(6),
            'subscription_expires_at'  => now()->addYear(),
            'invitation_code'          => 'FIRDAWS1',
            'bank_rib'                 => null,
            'default_monthly_fee'      => 200.0,
            'initial_treasury_balance' => 0.0,
            'initial_bank_balance'     => 0.0,
            'initial_balance_locked'   => false,
            'is_transparency_enabled'  => true,
        ]);

        // Syndic of Residence
        User::create([
            'tenant_id'        => $residence->id,
            'role'             => 'ADMIN_SYNDIC',
            'first_name'       => 'Mohammed',
            'last_name'        => 'Alaoui',
            'email'            => 'syndic@alfirdaws.ma',
            'phone'            => '+212661234567',
            'password'         => Hash::make('password'),
            'locale'           => 'ar',
            'theme_preference' => 'system',
            'status'           => 'ACTIVE',
        ]);

        // Residents of Residence
        User::create([
            'tenant_id'        => $residence->id,
            'role'             => 'RESIDENT',
            'first_name'       => 'Youssef',
            'last_name'        => 'Bennani',
            'email'            => 'youssef@example.com',
            'phone'            => '+212662000001',
            'password'         => Hash::make('password'),
            'locale'           => 'ar',
            'theme_preference' => 'system',
            'status'           => 'ACTIVE',
        ]);

        User::create([
            'tenant_id'        => $residence->id,
            'role'             => 'RESIDENT',
            'first_name'       => 'Fatima',
            'last_name'        => 'El Mansouri',
            'email'            => 'fatima@example.com',
            'phone'            => '+212663000002',
            'password'         => Hash::make('password'),
            'locale'           => 'ar',
            'theme_preference' => 'system',
            'status'           => 'ACTIVE',
        ]);

        // ─── 3. Tenant 2: مجمع سكني (عدة عمارات / بلوكات) ────────────
        $complex = Tenant::create([
            'name'                     => 'مجمع الأندلس السكني',
            'property_type'            => 'RESIDENTIAL_COMPLEX',
            'plan_type'                => 'PRO',
            'status'                   => 'ACTIVE',
            'trial_ends_at'            => now()->addMonths(6),
            'subscription_expires_at'  => now()->addYear(),
            'invitation_code'          => 'ANDALOUS',
            'bank_rib'                 => null,
            'default_monthly_fee'      => 250.0,
            'initial_treasury_balance' => 0.0,
            'initial_bank_balance'     => 0.0,
            'initial_balance_locked'   => false,
            'is_transparency_enabled'  => true,
        ]);

        // Syndic of Complex
        User::create([
            'tenant_id'        => $complex->id,
            'role'             => 'ADMIN_SYNDIC',
            'first_name'       => 'Omar',
            'last_name'        => 'Sebti',
            'email'            => 'syndic@complex-andalous.ma',
            'phone'            => '+212664000003',
            'password'         => Hash::make('password'),
            'locale'           => 'ar',
            'theme_preference' => 'system',
            'status'           => 'ACTIVE',
        ]);

        // Residents of Complex
        User::create([
            'tenant_id'        => $complex->id,
            'role'             => 'RESIDENT',
            'first_name'       => 'Karim',
            'last_name'        => 'Tazi',
            'email'            => 'karim@complex-andalous.ma',
            'phone'            => '+212665000004',
            'password'         => Hash::make('password'),
            'locale'           => 'ar',
            'theme_preference' => 'system',
            'status'           => 'ACTIVE',
        ]);

        User::create([
            'tenant_id'        => $complex->id,
            'role'             => 'RESIDENT',
            'first_name'       => 'Salma',
            'last_name'        => 'Idrissi',
            'email'            => 'salma@complex-andalous.ma',
            'phone'            => '+212666000005',
            'password'         => Hash::make('password'),
            'locale'           => 'ar',
            'theme_preference' => 'system',
            'status'           => 'ACTIVE',
        ]);

        $this->command->info('🏢 Smart Syndic seeded with clean trial accounts for Residence & Complex successfully!');
    }
}
