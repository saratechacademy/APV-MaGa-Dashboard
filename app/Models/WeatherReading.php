<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'temp_c',
        'humidity_pct',
        'pressure_hpa',
        'wind_speed_ms',
        'wind_direction',
        'irradiance_wm2',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at'    => 'datetime',
        'temp_c'         => 'float',
        'humidity_pct'   => 'float',
        'pressure_hpa'   => 'float',
        'wind_speed_ms'  => 'float',
        'irradiance_wm2' => 'float',
    ];

    // Une lecture appartient à un site
    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}