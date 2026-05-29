<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'country',
        'latitude',
        'longitude',
        'capacity_kw',
        'area_m2',
        'description',
        'status',
        'api_key',
    ];

    // Génère automatiquement le slug depuis le nom
    protected static function boot()

{
    parent::boot();
    static::creating(function ($site) {
        $site->slug = Str::slug($site->name) . '-' . uniqid();
        $site->api_key = 'apv-' . Str::random(32);
    });
}
    // Un site appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Un site a plusieurs lectures solaires
    public function solarReadings()
    {
        return $this->hasMany(SolarReading::class);
    }

    // Un site a plusieurs lectures d'eau
    public function waterReadings()
    {
        return $this->hasMany(WaterReading::class);
    }

    // Un site a plusieurs lectures d'irrigation
    public function irrigationReadings()
    {
        return $this->hasMany(IrrigationReading::class);
    }

    // Un site a plusieurs lectures météo
    public function weatherReadings()
    {
        return $this->hasMany(WeatherReading::class);
    }

    // Un site a plusieurs enregistrements agricoles
    public function agricultureRecords()
    {
        return $this->hasMany(AgricultureRecord::class);
    }

    // Dernière lecture solaire
    public function latestSolar()
    {
        return $this->hasOne(SolarReading::class)->latestOfMany('recorded_at');
    }

    // Dernière lecture eau
    public function latestWater()
    {
        return $this->hasOne(WaterReading::class)->latestOfMany('recorded_at');
    }

    // Dernière météo
    public function latestWeather()
    {
        return $this->hasOne(WeatherReading::class)->latestOfMany('recorded_at');
    }
public function categories()
{
    return $this->hasMany(\App\Models\SiteCategory::class)->orderBy('sort_order');
}

public function activeCategories()
{
    return $this->hasMany(\App\Models\SiteCategory::class)
                ->where('is_active', true)->orderBy('sort_order');
}
public function users()
{
    return $this->belongsToMany(User::class, 'site_user')
                ->withPivot('role')
                ->withTimestamps();
}
}