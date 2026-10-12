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
        'licor_device_serial',
    ];

    protected $casts = [
        'thingsboard_synced_at' => 'datetime',
        'licor_synced_at'       => 'datetime',
    ];

    /**
     * ok / late / failed for a site fed by ThingsBoard, null otherwise.
     * "late" also covers the server cron no longer running the scheduler.
     */
    public function thingsboardSyncState(): ?string
    {
        return $this->syncState($this->thingsboard_prefix, $this->thingsboard_sync_error,
            $this->thingsboard_synced_at, config('thingsboard.late_after_minutes'));
    }

    /** Same as thingsboardSyncState(), for the LI-COR (HOBOnet) station. */
    public function licorSyncState(): ?string
    {
        return $this->syncState($this->licor_device_serial, $this->licor_sync_error,
            $this->licor_synced_at, config('licor.late_after_minutes'));
    }

    private function syncState(?string $link, ?string $error, $syncedAt, int $lateAfterMinutes): ?string
    {
        if (!$link) {
            return null;
        }
        if ($error) {
            return 'failed';
        }

        return $syncedAt?->gte(now()->subMinutes($lateAfterMinutes)) ? 'ok' : 'late';
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