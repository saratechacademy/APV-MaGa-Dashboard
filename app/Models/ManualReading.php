<?php
// app/Models/ManualReading.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id', 'site_parameter_id', 'user_id', 'value', 'reading_date', 'zone', 'plant', 'notes'
    ];

    protected $casts = ['reading_date' => 'datetime'];

    /**
     * Readings entered together in one form form one saved record. Plot and
     * plant are part of it, so two plants entered in the same minute stay
     * two records.
     */
    public function recordKey(): string
    {
        return implode('||', [
            $this->reading_date->format('Y-m-d'),
            $this->created_at->format('Y-m-d H:i'),
            $this->zone,
            $this->plant,
        ]);
    }

    public function plotLabel(): ?string
    {
        return $this->zone ? (config('analysis.plots')[$this->zone] ?? $this->zone) : null;
    }

    public function site()      { return $this->belongsTo(Site::class); }
    public function parameter() { return $this->belongsTo(SiteParameter::class, 'site_parameter_id'); }
    public function user()      { return $this->belongsTo(User::class); }

    // Valeur castée selon le type du paramètre
    public function typedValue()
    {
        return match($this->parameter->data_type) {
            'float'   => (float) $this->value,
            'integer' => (int) $this->value,
            'boolean' => (bool) $this->value,
            default   => $this->value,
        };
    }
}