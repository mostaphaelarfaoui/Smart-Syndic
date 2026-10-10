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

        Schema::create('resident_apartments', function (Blueprint $table) use ($isPgsql) {
            if ($isPgsql) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->uuid('apartment_id');
            $table->foreign('apartment_id')->references('id')->on('apartments')->cascadeOnDelete();

            $table->string('occupancy_type', 20);
            $table->boolean('is_primary_contact')->default(true);

            $table->timestampsTz();

            // ─── One user per apartment link ──────────────────────────
            $table->unique(['user_id', 'apartment_id']);
            $table->index('apartment_id');
        });

        if ($isPgsql) {
            DB::statement("ALTER TABLE resident_apartments ADD CONSTRAINT chk_occupancy_type CHECK (occupancy_type IN ('OWNER', 'TENANT'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_apartments');
    }
};
