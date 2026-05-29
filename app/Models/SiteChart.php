<?php
// app/Models/SiteChart.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteChart extends Model
{
    protected $fillable = [
        'site_category_id','title','chart_type','sort_order',
        'height','col_span','show_legend','dual_axis','is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'show_legend' => 'boolean',
        'dual_axis'   => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(SiteCategory::class, 'site_category_id');
    }

    public function chartParameters()
    {
        return $this->hasMany(SiteChartParameter::class)->orderBy('sort_order');
    }

    public function parameters()
    {
        return $this->belongsToMany(SiteParameter::class, 'site_chart_parameters')
                    ->withPivot('color','axis','dashed','fill','sort_order')
                    ->orderBy('site_chart_parameters.sort_order');
    }
}