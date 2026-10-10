<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->uuid('actor_id');
            $table->foreign('actor_id')->references('id')->on('users')->cascadeOnDelete();

            $table->string('action', 255);

            $table->uuid('target_tenant_id')->nullable();
            $table->foreign('target_tenant_id')->references('id')->on('tenants')->nullOnDelete();

            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->json('metadata');

            $table->timestampTz('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));

            // ─── Indexes for querying ─────────────────────────────────
            $table->index('actor_id');
            $table->index('target_tenant_id');
            $table->index('created_at');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
