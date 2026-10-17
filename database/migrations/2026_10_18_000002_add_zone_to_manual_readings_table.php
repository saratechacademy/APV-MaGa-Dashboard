<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Field measurements record the plot they were taken on (reference or
     * APV), so yields and plant measurements can be compared between plots.
     * The agriculture parameters created by licor:setup get their metric,
     * which the Analysis page uses to find them on every site.
     */
    public function up(): void
    {
        Schema::table('manual_readings', function (Blueprint $table) {
            $table->string('zone', 30)->nullable()->after('reading_date');
        });

        DB::table('site_parameters')
            ->where('input_type', 'manual')
            ->whereNull('metric')
            ->whereIn('slug', array_keys(array_filter(config('analysis.quantities'), fn ($q) => ($q['source'] ?? null) === 'manual')))
            ->update(['metric' => DB::raw('slug')]);
    }

    public function down(): void
    {
        Schema::table('manual_readings', function (Blueprint $table) {
            $table->dropColumn('zone');
        });
    }
};
