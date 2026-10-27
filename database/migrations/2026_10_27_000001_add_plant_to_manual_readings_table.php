<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Researchers measure several plants on each plot: the plant a field
     * measurement was taken on is recorded next to its plot.
     */
    public function up(): void
    {
        Schema::table('manual_readings', function (Blueprint $table) {
            $table->string('plant', 30)->nullable()->after('zone');
        });
    }

    public function down(): void
    {
        Schema::table('manual_readings', function (Blueprint $table) {
            $table->dropColumn('plant');
        });
    }
};
