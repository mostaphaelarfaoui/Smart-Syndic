<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add payment workflow status columns (Feature 12):
     *
     * Workflow:  DRAFT → CONFIRMED → SENT_TO_RESIDENT
     *
     *  - DRAFT          : Payment created, syndic can edit/delete.
     *  - CONFIRMED      : Syndic verified and confirmed the receipt. Edit/delete locked.
     *  - SENT_TO_RESIDENT: Pushed to resident's account. Immutable + archived in audit.
     *
     * Also adds payment_snapshot JSON to audit_logs for dispute protection (Feature 3).
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 30)->default('DRAFT')->after('bank_receipt_proof');
            $table->timestampTz('confirmed_at')->nullable()->after('status');
            $table->timestampTz('sent_to_resident_at')->nullable()->after('confirmed_at');
            $table->index('status');
        });

        // Feature 3 — Store payment snapshot in audit_logs for immutable dispute records
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->json('payment_snapshot')->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'confirmed_at', 'sent_to_resident_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('payment_snapshot');
        });
    }
};
