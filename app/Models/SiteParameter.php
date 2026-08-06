<?php
// app/Models/SiteParameter.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_category_id', 'name', 'slug', 'unit', 'data_type', 'input_type', 'control_type',
        'group_name', 'site_parameter_group_id',
        'min_value', 'max_value', 'warning_threshold', 'critical_threshold', 'threshold_direction',
        'description', 'is_active', 'show_on_dashboard', 'sort_order',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'show_on_dashboard'  => 'boolean',
        'min_value'          => 'float',
        'max_value'          => 'float',
        'warning_threshold'  => 'float',
        'critical_threshold' => 'float',
    ];

    public function category() { return $this->belongsTo(SiteCategory::class, 'site_category_id'); }
    public function group() { return $this->belongsTo(SiteParameterGroup::class, 'site_parameter_group_id'); }
    public function manualReadings() { return $this->hasMany(ManualReading::class); }
    public function isSensor() { return $this->input_type === 'sensor'; }
    public function isManual() { return $this->input_type === 'manual'; }

    public function actuatorCommand() { return $this->hasOne(ActuatorCommand::class); }

    /**
     * Most recent sensor reading for this parameter. Eager-loadable
     * (`with('latestReading')`) so N sites × M parameters resolves to a single
     * query instead of one `latest()` lookup per parameter per site.
     */
    public function latestReading() { return $this->hasOne(SensorReading::class)->latestOfMany('read_at'); }

    /** Same idea as latestReading(), for manually-entered parameters. */
    public function latestManualReading() { return $this->hasOne(ManualReading::class)->latestOfMany('reading_date'); }

    /**
     * True if this parameter is a remotely controllable switch (relay, valve, pump, fan...).
     */
    public function isControllable(): bool
    {
        return ($this->control_type ?? 'readonly') === 'controllable';
    }

    /**
     * Threshold comparisons are direction-aware: "below" (default) alerts once
     * a value drops to/under the threshold — tank level, borehole level, that
     * kind of thing. "above" alerts once a value climbs to/over it — panel
     * temperature, anything where high is the dangerous direction.
     */
    private function crossesThreshold($value, ?float $threshold): bool
    {
        if ($value === null || !is_numeric($value) || $threshold === null) {
            return false;
        }
        return $this->threshold_direction === 'above'
            ? (float) $value >= $threshold
            : (float) $value <= $threshold;
    }

    public function isWarn($value): bool
    {
        return $this->crossesThreshold($value, $this->warning_threshold);
    }

    public function isCritical($value): bool
    {
        return $this->crossesThreshold($value, $this->critical_threshold);
    }

    public function isOutOfRange($value): bool
    {
        if ($value === null || !is_numeric($value)) {
            return false;
        }
        $v = (float) $value;
        return ($this->min_value !== null && $v < $this->min_value)
            || ($this->max_value !== null && $v > $this->max_value);
    }

    public static function defaultsFor(string $categorySlug): array
    {
        return match($categorySlug) {
            'solar' => [
                ['name'=>'Solar output',      'slug'=>'solar_output',      'unit'=>'kW',   'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Solar irradiance',  'slug'=>'solar_irradiance',  'unit'=>'W/m²', 'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Panel temperature', 'slug'=>'panel_temperature', 'unit'=>'°C',   'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true,  'warning_threshold'=>70, 'threshold_direction'=>'above'],
                ['name'=>'System efficiency', 'slug'=>'system_efficiency', 'unit'=>'%',    'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
            ],
            'water' => [
                ['name'=>'Borehole level',    'slug'=>'borehole_level',    'unit'=>'m',    'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true,  'warning_threshold'=>5],
                ['name'=>'Tank fill level',   'slug'=>'tank_fill_level',   'unit'=>'%',    'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true,  'warning_threshold'=>20],
                ['name'=>'Water pump',        'slug'=>'water_pump',        'unit'=>null,   'data_type'=>'switch',  'input_type'=>'sensor', 'control_type'=>'controllable', 'show_on_dashboard'=>true],
            ],
            'irrigation' => [
                ['name'=>'Flow rate',         'slug'=>'flow_rate',         'unit'=>'L/min','data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Zone A moisture',   'slug'=>'zone_a_moisture',   'unit'=>'%',    'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Zone B moisture',   'slug'=>'zone_b_moisture',   'unit'=>'%',    'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Zone C moisture',   'slug'=>'zone_c_moisture',   'unit'=>'%',    'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Valve 1',           'slug'=>'valve_1',           'unit'=>null,   'data_type'=>'switch',  'input_type'=>'sensor', 'control_type'=>'controllable', 'show_on_dashboard'=>true],
                ['name'=>'Valve 2',           'slug'=>'valve_2',           'unit'=>null,   'data_type'=>'switch',  'input_type'=>'sensor', 'control_type'=>'controllable', 'show_on_dashboard'=>true],
                ['name'=>'Valve 3',           'slug'=>'valve_3',           'unit'=>null,   'data_type'=>'switch',  'input_type'=>'sensor', 'control_type'=>'controllable', 'show_on_dashboard'=>true],
                ['name'=>'Cooling fan',       'slug'=>'cooling_fan',       'unit'=>null,   'data_type'=>'switch',  'input_type'=>'sensor', 'control_type'=>'controllable', 'show_on_dashboard'=>true],
            ],
            'weather' => [
                ['name'=>'Temperature',       'slug'=>'temperature',       'unit'=>'°C',   'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Humidity',          'slug'=>'humidity',          'unit'=>'%',    'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Air pressure',      'slug'=>'air_pressure',      'unit'=>'hPa',  'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Wind speed',        'slug'=>'wind_speed',        'unit'=>'m/s',  'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Wind direction',    'slug'=>'wind_direction',    'unit'=>'°',    'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
                ['name'=>'Solar irradiance',  'slug'=>'wx_irradiance',     'unit'=>'W/m²', 'data_type'=>'float',   'input_type'=>'sensor', 'show_on_dashboard'=>true],
            ],
            'agriculture' => [
                ['name'=>'Crop type',         'slug'=>'crop_type',         'unit'=>null,   'data_type'=>'string',  'input_type'=>'manual', 'show_on_dashboard'=>false, 'group_name'=>'Yield Data'],
                ['name'=>'Fresh yield',       'slug'=>'fresh_yield',       'unit'=>'kg',   'data_type'=>'float',   'input_type'=>'manual', 'show_on_dashboard'=>true,  'group_name'=>'Yield Data'],
                ['name'=>'Dry yield',         'slug'=>'dry_yield',         'unit'=>'kg',   'data_type'=>'float',   'input_type'=>'manual', 'show_on_dashboard'=>true, 'group_name'=>'Yield Data'],
                ['name'=>'Plant height',      'slug'=>'plant_height',      'unit'=>'cm',   'data_type'=>'float',   'input_type'=>'manual', 'show_on_dashboard'=>true,  'group_name'=>'Plant Measurements'],
                ['name'=>'Canopy cover',      'slug'=>'canopy_cover',      'unit'=>'%',    'data_type'=>'float',   'input_type'=>'manual', 'show_on_dashboard'=>true,  'group_name'=>'Plant Measurements'],
                ['name'=>'Plant health',      'slug'=>'plant_health',      'unit'=>null,   'data_type'=>'string',  'input_type'=>'manual', 'show_on_dashboard'=>false, 'group_name'=>'Plant Measurements'],
            ],
            default => [],
        };
    }
}