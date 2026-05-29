<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgricultureRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'date',
        'record_type',
        'zone',
        'crop_type',
        'growth_stage',
        'fresh_yield_kg',
        'dry_yield_kg',
        'plot_area_m2',
        'plant_height_cm',
        'leaf_area_index',
        'canopy_cover_pct',
        'chlorophyll_spad',
        'plant_health',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'date'             => 'date',
        'fresh_yield_kg'   => 'float',
        'dry_yield_kg'     => 'float',
        'plot_area_m2'     => 'float',
        'plant_height_cm'  => 'float',
        'leaf_area_index'  => 'float',
        'canopy_cover_pct' => 'float',
        'chlorophyll_spad' => 'float',
    ];

    // Un enregistrement appartient à un site
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    // Calcul du rendement par m²
    public function yieldPerM2(): ?float
    {
        if ($this->fresh_yield_kg && $this->plot_area_m2) {
            return round($this->fresh_yield_kg / $this->plot_area_m2, 2);
        }
        return null;
    }
}