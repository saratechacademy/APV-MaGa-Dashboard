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
        // What the parameter measures (air_temperature, soil_moisture,
        // par_reference...), independent of its name, slug or source. The
        // "All Sites" overview used to look parameters up by hardcoded
        // default slugs and to plot "the first parameter of the Solar
        // category" under a fixed "Solar Output (kW)" title, which is wrong
        // for any site not built from the default template.
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->string('metric', 50)->nullable()->after('data_type')->index();
        });

        // Existing parameters: infer it once from what created them.
        DB::table('site_parameters')
            ->join('site_categories', 'site_categories.id', '=', 'site_parameters.site_category_id')
            ->select('site_parameters.id', 'site_parameters.slug', 'site_parameters.name', 'site_categories.slug as category')
            ->orderBy('site_parameters.id')
            ->chunk(500, function ($parameters) {
                foreach ($parameters as $p) {
                    if ($metric = $this->infer($p->slug, $p->name, $p->category)) {
                        DB::table('site_parameters')->where('id', $p->id)->update(['metric' => $metric]);
                    }
                }
            });
    }

    private function infer(string $slug, string $name, string $category): ?string
    {
        // LI-COR parameters: slug is the sensor serial, the measurement is in the name.
        if (str_starts_with($slug, 'licor_')) {
            if (str_ends_with($name, '– PAR')) {
                return 'par_' . Str::slug(trim(Str::before($name, '–')), '_');
            }

            foreach ([
                'Air temperature' => 'air_temperature', 'Relative humidity' => 'relative_humidity',
                'Dew point' => 'dew_point', 'Wind speed' => 'wind_speed', 'Gust speed' => 'gust_speed',
                'Wind direction' => 'wind_direction', 'Rain' => 'rain', 'Air pressure' => 'air_pressure',
                'PAR' => 'par',
            ] as $prefix => $metric) {
                if (str_starts_with($name, $prefix)) {
                    return $metric;
                }
            }

            return null;
        }

        // Default-template parameters.
        $defaults = [
            'solar' => ['solar_output' => 'solar_output'],
            'water' => ['borehole_level' => 'borehole_level', 'tank_fill_level' => 'tank_fill_level'],
            'weather' => ['temperature' => 'air_temperature', 'humidity' => 'relative_humidity', 'wind_speed' => 'wind_speed'],
        ];
        if (isset($defaults[$category][$slug])) {
            return $defaults[$category][$slug];
        }

        // ThingsBoard parameters (and default "zone_x_moisture"): slug ends with the measurement.
        foreach ([
            '_moisture' => 'soil_moisture', '_conductivity' => 'soil_conductivity',
            '_tank_volume' => 'tank_volume', '_tank_level' => 'tank_level', '_water_level' => 'water_level',
            '_total_flow' => 'total_flow', '_turbidity' => 'turbidity', '_valve_state' => 'valve_state',
        ] as $suffix => $metric) {
            if (str_ends_with($slug, $suffix)) {
                return $metric;
            }
        }

        return $category === 'irrigation' && str_ends_with($slug, '_temperature') ? 'soil_temperature' : null;
    }

    public function down(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->dropIndex(['metric']);
            $table->dropColumn('metric');
        });
    }
};
