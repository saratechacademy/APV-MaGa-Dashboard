<?php
// app/Models/ActuatorCommand.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActuatorCommand extends Model
{
    protected $fillable = [
        'site_id',
        'site_parameter_id',
        'desired_state',
        'reported_state',
        'reported_at',
        'updated_by',
    ];

    protected $casts = [
        'desired_state'  => 'integer',
        'reported_state' => 'integer',
        'reported_at'    => 'datetime',
    ];

    public function site() { return $this->belongsTo(Site::class); }
    public function parameter() { return $this->belongsTo(SiteParameter::class, 'site_parameter_id'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }

    /**
     * True if the desired state matches the last reported state from the ESP32.
     * Null if no report has been received yet.
     */
    public function isSynced(): ?bool
    {
        if ($this->reported_state === null) {
            return null;
        }
        return $this->desired_state === $this->reported_state;
    }
}