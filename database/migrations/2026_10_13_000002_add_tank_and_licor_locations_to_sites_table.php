<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Two things that are facts about one site, yet lived in config files
        // shared by every site:
        //  - the water tank's geometry, from which the tank volume is computed
        //    (was a fixed "radius 180 cm, one tank" for everybody);
        //  - where each HOBOnet sensor stands (Reference, Under PV...), keyed
        //    by sensor serial (was a list of serials in config/licor.php).
        Schema::table('sites', function (Blueprint $table) {
            $table->float('tank_radius_cm')->nullable()->after('area_m2');
            $table->unsignedSmallInteger('tank_count')->default(1)->after('tank_radius_cm');
            $table->json('licor_locations')->nullable()->after('licor_device_serial');
        });

        // Sites that already have a computed tank volume were computed with
        // the old fixed geometry: keep them consistent.
        $withVolume = DB::table('site_parameters')
            ->join('site_categories', 'site_categories.id', '=', 'site_parameters.site_category_id')
            ->where('site_parameters.metric', 'tank_volume')
            ->distinct()
            ->pluck('site_categories.site_id');

        DB::table('sites')->whereIn('id', $withVolume)->update(['tank_radius_cm' => 180, 'tank_count' => 1]);

        // Locations already given to LI-COR parameters ("Under PV – PAR").
        $located = DB::table('site_parameters')
            ->join('site_categories', 'site_categories.id', '=', 'site_parameters.site_category_id')
            ->where('site_parameters.slug', 'like', 'licor%')
            ->where('site_parameters.metric', 'like', 'par%')
            ->where('site_parameters.name', 'like', '% – PAR')
            ->get(['site_categories.site_id', 'site_parameters.slug', 'site_parameters.name']);

        foreach ($located->groupBy('site_id') as $siteId => $parameters) {
            $locations = [];
            foreach ($parameters as $p) {
                // licor_22261664_1 => sensor 22261664
                $locations[explode('_', $p->slug)[1] ?? ''] = trim(Str::before($p->name, '–'));
            }
            unset($locations['']);

            DB::table('sites')->where('id', $siteId)->update(['licor_locations' => json_encode($locations)]);
        }
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['tank_radius_cm', 'tank_count', 'licor_locations']);
        });
    }
};
