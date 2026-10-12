<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add is_billable flag to apartments table (Feature 4 & 5):
     *
     * Distinction:
     *  - is_billable = false → apartment owned by property developer (المنعش العقاري),
     *    no residents, excluded from financial calculations entirely.
     *  - is_billable = true  → apartment has or should have residents; always included
     *    in monthly expected totals even if resident hasn't registered yet.
     */
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->boolean('is_billable')->default(true)->after('monthly_fee');
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn('is_billable');
        });
    }
};
