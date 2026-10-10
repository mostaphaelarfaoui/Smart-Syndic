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

        // ─── Polls ────────────────────────────────────────────────────
        Schema::create('polls', function (Blueprint $table) use ($isPgsql) {
            if ($isPgsql) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users');

            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->timestampTz('expires_at');
            $table->boolean('is_active')->default(true);

            $table->timestampsTz();

            $table->index('tenant_id');
        });

        // ─── Poll Options ─────────────────────────────────────────────
        Schema::create('poll_options', function (Blueprint $table) use ($isPgsql) {
            if ($isPgsql) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('poll_id');
            $table->foreign('poll_id')->references('id')->on('polls')->cascadeOnDelete();

            $table->string('option_text', 255);

            $table->timestampsTz();
        });

        // ─── Poll Votes (One vote per apartment per poll) ─────────────
        Schema::create('poll_votes', function (Blueprint $table) use ($isPgsql) {
            if ($isPgsql) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('poll_id');
            $table->foreign('poll_id')->references('id')->on('polls')->cascadeOnDelete();

            $table->uuid('poll_option_id');
            $table->foreign('poll_option_id')->references('id')->on('poll_options')->cascadeOnDelete();

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->uuid('apartment_id');
            $table->foreign('apartment_id')->references('id')->on('apartments')->cascadeOnDelete();

            $table->timestampsTz();

            // ─── Enforce one vote per apartment per poll ───────────────
            $table->unique(['poll_id', 'apartment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('poll_options');
        Schema::dropIfExists('polls');
    }
};
