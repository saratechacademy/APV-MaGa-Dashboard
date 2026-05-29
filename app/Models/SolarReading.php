<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolarReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'power_kw',
        'irradiance_wm2',
        'panel_temp_c',
        'efficiency_pct',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'power_kw'    => 'float',
        'irradiance_wm2' => 'float',
        'panel_temp_c'   => 'float',
        'efficiency_pct' => 'float',
    ];

    // Une lecture appartient à un site
    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}