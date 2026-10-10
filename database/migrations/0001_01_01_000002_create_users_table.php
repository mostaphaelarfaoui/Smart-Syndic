<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isPgsql = DB::getDriverName() === 'pgsql';

        Schema::create('users', function (Blueprint $table) use ($isPgsql) {
            if ($isPgsql) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->string('role', 30);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->unique();
            $table->string('phone', 30)->unique();
            $table->string('password', 255); // Argon2id / Bcrypt

            $table->string('locale', 5)->default('ar');
            $table->string('theme_preference', 10)->default('system');
            $table->string('status', 20)->default('PENDING');

            $table->text('fcm_device_token')->nullable();

            $table->timestampsTz();
        });

        // ─── PostgreSQL CHECK Constraints ─────────────────────────────
        if ($isPgsql) {
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('SUPER_ADMIN', 'ADMIN_SYNDIC', 'RESIDENT'))");
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_locale CHECK (locale IN ('ar', 'fr', 'en'))");
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_theme CHECK (theme_preference IN ('light', 'dark', 'system'))");
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_status CHECK (status IN ('PENDING', 'ACTIVE', 'BLOCKED'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
