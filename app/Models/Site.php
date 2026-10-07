<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Site extends Model
{
    use HasFactory;

    // Deliberately excludes 'status' and 'api_key': every write site sets
    // these via direct property assignment instead of a fillable array
    // (status: $site->status = ...; $site->save() — api_key: auto-generated
    // in boot() below), so a future $request->all() slip can't silently
    // reactivate a suspended site or let a caller pick their own device key.
    // 'slug' stays out too — always derived from the name in boot().
    protected $fillable = [
        'user_id',
        'name',
        'country',
        'latitude',
        'longitude',
        'capacity_kw',
        'area_m2',
        'description',
        'thingsboard_prefix',
    ];

    protected $casts = [
        'thingsboard_synced_at' => 'datetime',
    ];

    /**
     * ok / late / failed for a site fed by ThingsBoard, null otherwise.
     * "late" also covers the server cron no longer running the scheduler.
     */
    public function thingsboardSyncState(): ?string
    {
        if (!$this->thingsboard_prefix) {
            return null;
        }
        if ($this->thingsboard_sync_error) {
            return 'failed';
        }

        $lateAfter = now()->subMinutes(config('thingsboard.late_after_minutes'));

        return $this->thingsboard_synced_at?->gte($lateAfter) ? 'ok' : 'late';
    }

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