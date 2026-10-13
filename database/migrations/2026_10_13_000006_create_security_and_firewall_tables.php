<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blocked_ips')) {
            Schema::create('blocked_ips', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->unique();
                $table->string('reason')->default('محاولة غير مصرح بها أو نشاط مشبوه');
                $table->foreignUuid('blocked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('security_incidents')) {
            Schema::create('security_incidents', function (Blueprint $table) {
                $table->id();
                $table->string('incident_type', 50); // FAILED_LOGIN, BRUTE_FORCE, FRAUD_ALERT, SUSPICIOUS_VELOCITY, BLOCKED_ATTEMPT, TAMPER_ALERT
                $table->string('severity', 20)->default('MEDIUM'); // LOW, MEDIUM, HIGH, CRITICAL
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('target_email')->nullable();
                $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                $table->text('details')->nullable();
                $table->string('status', 20)->default('OPEN'); // OPEN, RESOLVED, IGNORED
                $table->timestamps();

                $table->index(['incident_type', 'status']);
                $table->index('ip_address');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
        Schema::dropIfExists('blocked_ips');
    }
};
