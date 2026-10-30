<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('stamp_image_path');
            }
            if (!Schema::hasColumn('tenants', 'receipt_footer_note')) {
                $table->text('receipt_footer_note')->nullable()->after('payment_due_day');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
            if (Schema::hasColumn('tenants', 'receipt_footer_note')) {
                $table->dropColumn('receipt_footer_note');
            }
        });
    }
};
