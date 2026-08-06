<?php
// app/Models/SiteChartParameter.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteChartParameter extends Model
{
    protected $fillable = [
        'site_chart_id','site_parameter_id','color','axis','dashed','fill','sort_order',
    ];

    protected $casts = ['dashed'=>'boolean','fill'=>'boolean'];

    public function chart()    { return $this->belongsTo(SiteChart::class); }
    public function parameter(){ return $this->belongsTo(SiteParameter::class, 'site_parameter_id'); }
}