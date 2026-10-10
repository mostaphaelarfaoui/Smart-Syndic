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

        Schema::create('expenses', function (Blueprint $table) use ($isPgsql) {
            if ($isPgsql) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->uuid('building_id')->nullable();
            $table->foreign('building_id')->references('id')->on('buildings')->nullOnDelete();

            $table->string('category', 50);
            $table->string('title', 255);
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->text('invoice_proof_path')->nullable();

            $table->timestampsTz();

            $table->index('tenant_id');
            $table->index('expense_date');
        });

        if ($isPgsql) {
            DB::statement("ALTER TABLE expenses ADD CONSTRAINT chk_expenses_amount CHECK (amount > 0)");
            DB::statement("ALTER TABLE expenses ADD CONSTRAINT chk_expenses_category CHECK (category IN ('ELEVATOR', 'CLEANING', 'SECURITY', 'WATER_ELECTRICITY', 'GARDENING', 'MAINTENANCE', 'OTHER'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
