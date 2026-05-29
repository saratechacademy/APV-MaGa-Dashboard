<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteCategory extends Model
{
    protected $fillable = [
        'site_id', 'name', 'slug', 'icon', 'color',
        'description', 'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function site()       { return $this->belongsTo(Site::class); }

    public function parameters() { return $this->hasMany(SiteParameter::class)->orderBy('sort_order'); }

    public function activeParameters() { return $this->hasMany(SiteParameter::class)->where('is_active', true)->orderBy('sort_order'); }

    public function charts()     { return $this->hasMany(SiteChart::class)->orderBy('sort_order'); }

    public function activeCharts() { return $this->hasMany(SiteChart::class)->where('is_active', true)->orderBy('sort_order'); }

    public static function defaults(): array
    {
        return [
            ['name'=>'Solar',       'slug'=>'solar',       'icon'=>'☀',  'color'=>'#15803d'],
            ['name'=>'Water',       'slug'=>'water',       'icon'=>'💧', 'color'=>'#1d6ed8'],
            ['name'=>'Irrigation',  'slug'=>'irrigation',  'icon'=>'🔋', 'color'=>'#7c3aed'],
            ['name'=>'Weather',     'slug'=>'weather',     'icon'=>'🌡', 'color'=>'#b45309'],
            ['name'=>'Agriculture', 'slug'=>'agriculture', 'icon'=>'🌿', 'color'=>'#15803d'],
        ];
    }
}