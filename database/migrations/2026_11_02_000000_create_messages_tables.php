<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('sender_id')->constrained('users')->onDelete('cascade');
            $table->string('sender_role', 30)->default('ADMIN_SYNDIC'); // SUPER_ADMIN, ADMIN_SYNDIC, RESIDENT
            $table->string('target_type', 30)->default('ALL_RESIDENTS'); // ALL_RESIDENTS, INDIVIDUAL, GROUP, SYNDIC, ALL_PLATFORM
            $table->string('subject');
            $table->text('body');
            $table->string('category', 30)->default('GENERAL'); // ANNOUNCEMENT, GENERAL, INQUIRY, COMPLAINT, MAINTENANCE, FINANCIAL
            $table->string('priority', 20)->default('NORMAL'); // NORMAL, HIGH, URGENT
            $table->json('target_metadata')->nullable();
            $table->foreignUuid('parent_id')->nullable()->constrained('messages')->onDelete('cascade');
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });

        Schema::create('message_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('message_id')->constrained('messages')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_recipients');
        Schema::dropIfExists('messages');
    }
};
