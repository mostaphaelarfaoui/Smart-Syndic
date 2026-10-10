<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isPgsql = DB::getDriverName() === 'pgsql';

        // Enable UUID generation for PostgreSQL
        if ($isPgsql) {
            Schema::getConnection()->statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
        }

        Schema::create('tenants', function (Blueprint $table) use ($isPgsql) {
            if ($isPgsql) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->string('name', 255);
            $table->string('property_type', 50);
            $table->string('plan_type', 30)->default('TRIAL');
            $table->string('status', 30)->default('PENDING_APPROVAL');

            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('subscription_expires_at')->nullable();

            $table->string('invitation_code', 32)->unique();
            $table->string('bank_rib', 34)->nullable();

            $table->boolean('is_transparency_enabled')->default(false);

            $table->timestampsTz();

            // ─── CHECK constraints ────────────────────────────────────
            if ($isPgsql) {
                $table->rawIndex(
                    "((property_type IN ('SINGLE_BUILDING', 'MULTI_BUILDING_COMPLEX')))",
                    'chk_tenants_property_type'
                );
            }
        });

        // Add CHECK constraints via raw SQL for PostgreSQL
        if ($isPgsql) {
            DB::statement("ALTER TABLE tenants ADD CONSTRAINT chk_tenants_plan_type CHECK (plan_type IN ('TRIAL', 'PRO', 'ENTERPRISE'))");
            DB::statement("ALTER TABLE tenants ADD CONSTRAINT chk_tenants_status CHECK (status IN ('PENDING_APPROVAL', 'ACTIVE', 'SUSPENDED', 'EXPIRED'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
