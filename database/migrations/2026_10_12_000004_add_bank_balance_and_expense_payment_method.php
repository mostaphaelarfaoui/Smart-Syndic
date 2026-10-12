<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add initial_bank_balance to tenants (Feature 9)
     * and payment_method to expenses (Feature 11).
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('initial_bank_balance', 12, 2)->default(0)->after('initial_treasury_balance');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('payment_method', 20)->default('CASH')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('initial_bank_balance');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
