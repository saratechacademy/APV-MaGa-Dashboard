<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'organisation',
        'phone',
        'country',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // Un utilisateur peut avoir plusieurs sites (propriétaire)
    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    // Sites assignés via table pivot site_user
    public function assignedSites()
    {
        return $this->belongsToMany(Site::class, 'site_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // Helpers rôles
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isObservateur(): bool
    {
        return $this->role === 'observateur';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}