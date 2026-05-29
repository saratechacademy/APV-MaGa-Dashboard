<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    protected $fillable = [
        'site_id',
        'site_parameter_id',
        'value',
        'value_text',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'value'   => 'float',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function parameter()
    {
        return $this->belongsTo(SiteParameter::class, 'site_parameter_id');
    }

    // Valeur finale (numérique ou texte)
    public function getDisplayValueAttribute(): string
    {
        return $this->value_text ?? (string) $this->value;
    }
}