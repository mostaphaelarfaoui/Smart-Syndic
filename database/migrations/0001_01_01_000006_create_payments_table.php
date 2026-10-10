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

        Schema::create('payments', function (Blueprint $table) use ($isPgsql) {
            if ($isPgsql) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->uuid('apartment_id');
            $table->foreign('apartment_id')->references('id')->on('apartments')->restrictOnDelete();

            $table->uuid('recorded_by');
            $table->foreign('recorded_by')->references('id')->on('users');

            $table->decimal('amount_paid', 10, 2);
            $table->string('payment_method', 20);
            $table->string('cheque_reference', 100)->nullable();
            $table->date('payment_date');

            // JSON array of covered months, e.g. ["2026-09", "2026-10"]
            $table->json('covered_months');

            $table->boolean('is_partial')->default(false);
            $table->decimal('remaining_balance', 10, 2)->default(0.00);

            $table->string('receipt_number', 50)->unique();
            $table->text('receipt_pdf_path');
            $table->text('bank_receipt_proof')->nullable();

            $table->timestampsTz();

            // ─── Indexes ──────────────────────────────────────────────
            $table->index('tenant_id');
            $table->index('apartment_id');
            $table->index('payment_date');
        });

        // ─── CHECK Constraints ────────────────────────────────────────
        if ($isPgsql) {
            DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_payments_amount CHECK (amount_paid > 0)");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_payments_method CHECK (payment_method IN ('CASH', 'BANK_TRANSFER', 'CHEQUE'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
