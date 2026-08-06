<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    // Deliberately excludes 'role' and 'status': every write site sets these
    // via direct property assignment ($user->role = ...; $user->save();)
    // instead of a fillable array, so a future $request->all() slip can't
    // become a privilege-escalation bug.
    protected $fillable = [
        'name',
        'email',
        'password',
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

    /**
     * Envoie l'email de réinitialisation de mot de passe avec le design
     * APV-MaGa (au lieu du template Laravel par défaut).
     */
    public function sendPasswordResetNotification($token): void
    {
        $expireMinutes = (int) config('auth.passwords.users.expire', 60);

        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ]);

        Mail::to($this->email)->send(new ResetPasswordMail($this, $resetUrl, $expireMinutes));
    }
}