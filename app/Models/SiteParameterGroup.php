<?php
// app/Models/SiteParameterGroup.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteParameterGroup extends Model
{
    protected $fillable = ['site_category_id', 'name', 'color', 'sort_order'];

    public function category()   { return $this->belongsTo(SiteCategory::class, 'site_category_id'); }
    public function parameters() { return $this->hasMany(SiteParameter::class)->orderBy('sort_order'); }

    /**
     * Same palette used for categories/chart series, so groups stay visually
     * consistent with the rest of the dashboard instead of defaulting to gray.
     */
    public static function palette(): array
    {
        return ['#15803d', '#1d6ed8', '#7c3aed', '#b45309', '#0891b2', '#be123c', '#0f766e', '#7e22ce'];
    }

    public static function nextPaletteColor(int $existingCount): string
    {
        $palette = self::palette();
        return $palette[$existingCount % count($palette)];
    }
}
