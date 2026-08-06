<?php
// app/Models/ManualReading.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id', 'site_parameter_id', 'user_id', 'value', 'reading_date', 'notes'
    ];

    protected $casts = ['reading_date' => 'datetime'];

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