<?php

use App\Support\Zones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where a parameter is measured (reference plot, under the panels…), for
     * the Analysis page. Filled from what the syncs already recorded: the PAR
     * metric suffix, LI-COR location groups and ThingsBoard field groups.
     */
    public function up(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->string('zone', 30)->nullable()->after('metric');
        });

        $parameters = DB::table('site_parameters')
            ->leftJoin('site_parameter_groups', 'site_parameter_groups.id', '=', 'site_parameters.site_parameter_group_id')
            ->select('site_parameters.id', 'site_parameters.metric', 'site_parameter_groups.name as group_name')
            ->get();

        foreach ($parameters as $parameter) {
            if ($zone = Zones::infer($parameter->metric, $parameter->group_name)) {
                DB::table('site_parameters')->where('id', $parameter->id)->update(['zone' => $zone]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->dropColumn('zone');
        });
    }
};
