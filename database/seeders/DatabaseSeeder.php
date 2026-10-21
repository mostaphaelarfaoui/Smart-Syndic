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

        // ─── 2. Clean Tenant (Syndic) ─────────────────────────────────
        $tenant = Tenant::create([
            'name'                     => 'Résidence Al Firdaws',
            'property_type'            => 'MULTI_BUILDING_COMPLEX',
            'plan_type'                => 'PRO',
            'status'                   => 'ACTIVE',
            'trial_ends_at'            => now()->addMonths(6),
            'subscription_expires_at'  => now()->addYear(),
            'invitation_code'          => Tenant::generateInvitationCode(),
            'bank_rib'                 => null,
            'default_monthly_fee'      => 200.0,
            'initial_treasury_balance' => 0.0,
            'initial_bank_balance'     => 0.0,
            'initial_balance_locked'   => false,
            'is_transparency_enabled'  => true,
        ]);

        // ─── 3. Syndic Admin User ─────────────────────────────────────
        $syndic = User::create([
            'tenant_id'        => $tenant->id,
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

        // ─── 4. Residents (Youssef & Fatima without apartments) ───────
        $residentNames = [
            ['Youssef', 'Bennani', '+212662000001'],
            ['Fatima', 'El Mansouri', '+212663000002'],
        ];

        foreach ($residentNames as [$first, $last, $phone]) {
            User::create([
                'tenant_id'        => $tenant->id,
                'role'             => 'RESIDENT',
                'first_name'       => $first,
                'last_name'        => $last,
                'email'            => strtolower($first) . '@example.com',
                'phone'            => $phone,
                'password'         => Hash::make('password'),
                'locale'           => 'ar',
                'theme_preference' => 'system',
                'status'           => 'ACTIVE',
            ]);
        }

        $this->command->info('🏢 Smart Syndic seeded with clean trial accounts successfully!');
        $this->command->info("   Super Admin: admin@smartsyndic.com / password");
        $this->command->info("   Syndic Admin: syndic@alfirdaws.ma / password");
        $this->command->info("   Resident: youssef@example.com / password");
        $this->command->info("   Resident: fatima@example.com / password");
        $this->command->info("   Invitation Code: {$tenant->invitation_code}");
    }
}
