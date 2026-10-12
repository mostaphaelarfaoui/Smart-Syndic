<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add new feature columns to the tenants table:
     *  - stamp_image_path       : path to the syndic stamp image (Feature 1)
     *  - initial_treasury_balance: opening cash balance entered at registration (Feature 9)
     *  - initial_balance_locked : prevents editing after first save (Feature 9)
     *  - receipt_sequence       : atomic sequential receipt counter (Feature 13)
     *  - contact_info           : admin contact info shown to pending syndics (Feature 14)
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Feature 1 — Stamp image
            $table->string('stamp_image_path')->nullable()->after('bank_rib');

            // Feature 9 — Opening treasury balance (set once, locked after)
            $table->decimal('initial_treasury_balance', 12, 2)->default(0)->after('stamp_image_path');
            $table->boolean('initial_balance_locked')->default(false)->after('initial_treasury_balance');

            // Feature 13 — Sequential receipt number per tenant
            $table->unsignedInteger('receipt_sequence')->default(0)->after('initial_balance_locked');

            // Feature 14 — Contact info shown to pending syndics waiting for approval
            $table->json('contact_info')->nullable()->after('receipt_sequence');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'stamp_image_path',
                'initial_treasury_balance',
                'initial_balance_locked',
                'receipt_sequence',
                'contact_info',
            ]);
        });
    }
};
