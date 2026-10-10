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

        Schema::create('suggestions', function (Blueprint $table) use ($isPgsql) {
            if ($isPgsql) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->uuid('apartment_id');
            $table->foreign('apartment_id')->references('id')->on('apartments')->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('description');
            $table->text('attachment_url')->nullable();

            $table->string('status', 30)->default('PENDING');
            $table->text('admin_notes')->nullable();

            $table->timestampsTz();

            $table->index('tenant_id');
            $table->index('user_id');
        });

        if ($isPgsql) {
            DB::statement("ALTER TABLE suggestions ADD CONSTRAINT chk_suggestions_status CHECK (status IN ('PENDING', 'ACCEPTED', 'REJECTED', 'CONVERTED_TO_POLL'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};
