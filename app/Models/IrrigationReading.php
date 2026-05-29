<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IrrigationReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'flow_rate_lmin',
        'moisture_zone_a_pct',
        'moisture_zone_b_pct',
        'moisture_zone_c_pct',
        'valve_v01_open',
        'valve_v02_open',
        'valve_v03_open',
        'pump_runtime_min',
        'water_collected_l',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at'         => 'datetime',
        'flow_rate_lmin'      => 'float',
        'moisture_zone_a_pct' => 'float',
        'moisture_zone_b_pct' => 'float',
        'moisture_zone_c_pct' => 'float',
        'valve_v01_open'      => 'boolean',
        'valve_v02_open'      => 'boolean',
        'valve_v03_open'      => 'boolean',
        'pump_runtime_min'    => 'float',
        'water_collected_l'   => 'float',
    ];

    // Une lecture appartient à un site
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    // Nombre de vannes ouvertes
    public function openValvesCount(): int
    {
        return collect([
            $this->valve_v01_open,
            $this->valve_v02_open,
            $this->valve_v03_open,
        ])->filter()->count();
    }
}