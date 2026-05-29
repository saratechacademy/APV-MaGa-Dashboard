<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'borehole_level_m',
        'tank_fill_pct',
        'min_threshold_m',
        'status',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at'      => 'datetime',
        'borehole_level_m' => 'float',
        'tank_fill_pct'    => 'float',
        'min_threshold_m'  => 'float',
    ];

    // Une lecture appartient à un site
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    // Vérifie si le niveau est en dessous du seuil
    public function isWarning(): bool
    {
        return $this->min_threshold_m
            && $this->borehole_level_m < $this->min_threshold_m;
    }
}