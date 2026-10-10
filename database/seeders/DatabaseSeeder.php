<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\ResidentApartment;
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

        // ─── 2. Sample Tenant (Syndic) ────────────────────────────────
        $tenant = Tenant::create([
            'name'                    => 'Résidence Al Firdaws',
            'property_type'           => 'MULTI_BUILDING_COMPLEX',
            'plan_type'               => 'PRO',
            'status'                  => 'ACTIVE',
            'trial_ends_at'           => now()->addMonths(6),
            'subscription_expires_at' => now()->addYear(),
            'invitation_code'         => Tenant::generateInvitationCode(),
            'bank_rib'                => '013780000017401234567890',
            'is_transparency_enabled' => true,
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

        // ─── 4. Buildings ─────────────────────────────────────────────
        $blocA = Building::create([
            'tenant_id'    => $tenant->id,
            'name'         => 'Bloc A',
            'total_floors' => 5,
        ]);

        $blocB = Building::create([
            'tenant_id'    => $tenant->id,
            'name'         => 'Bloc B',
            'total_floors' => 4,
        ]);

        // ─── 5. Apartments ───────────────────────────────────────────
        $apartments = [];
        foreach (range(1, 5) as $floor) {
            foreach (['Gauche', 'Droite'] as $side) {
                $apartments[] = Apartment::create([
                    'building_id' => $blocA->id,
                    'unit_number' => "Étage {$floor} - {$side}",
                    'floor'       => $floor,
                    'monthly_fee' => 300.00,
                ]);
            }
        }

        foreach (range(1, 4) as $floor) {
            foreach (['Gauche', 'Droite'] as $side) {
                Apartment::create([
                    'building_id' => $blocB->id,
                    'unit_number' => "Étage {$floor} - {$side}",
                    'floor'       => $floor,
                    'monthly_fee' => 250.00,
                ]);
            }
        }

        // ─── 6. Sample Residents ──────────────────────────────────────
        $residentNames = [
            ['Youssef', 'Bennani', '+212662000001'],
            ['Fatima', 'El Mansouri', '+212663000002'],
            ['Ahmed', 'Chakir', '+212664000003'],
        ];

        foreach ($residentNames as $index => [$first, $last, $phone]) {
            $resident = User::create([
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

            if (isset($apartments[$index])) {
                ResidentApartment::create([
                    'user_id'            => $resident->id,
                    'apartment_id'       => $apartments[$index]->id,
                    'occupancy_type'     => $index === 2 ? 'TENANT' : 'OWNER',
                    'is_primary_contact' => true,
                ]);
            }
        }

        $this->command->info('🏢 Smart Syndic seeded successfully!');
        $this->command->info("   Super Admin: admin@smartsyndic.com / password");
        $this->command->info("   Syndic Admin: syndic@alfirdaws.ma / password");
        $this->command->info("   Invitation Code: {$tenant->invitation_code}");
    }
}
