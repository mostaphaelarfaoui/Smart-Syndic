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

        Schema::create('apartments', function (Blueprint $table) use ($isPgsql) {
            if ($isPgsql) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            } else {
                $table->uuid('id')->primary();
            }

            $table->uuid('building_id');
            $table->foreign('building_id')->references('id')->on('buildings')->cascadeOnDelete();

            $table->string('unit_number', 50);
            $table->integer('floor');
            $table->decimal('monthly_fee', 10, 2)->default(0.00);

            $table->timestampsTz();

            // ─── Unique constraint: one unit_number per building ──────
            $table->unique(['building_id', 'unit_number']);
            $table->index('building_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
