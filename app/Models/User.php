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
    // The role values stored in users.role and site_user.role. Everything
    // else refers to these constants, never to the strings.
    public const ROLE_ADMIN    = 'admin';
    public const ROLE_AGENT    = 'agent';
    public const ROLE_OBSERVER = 'observateur';

    /** Roles a user account can have => label. */
    public const ROLES = [
        self::ROLE_AGENT    => 'Agent',
        self::ROLE_OBSERVER => 'Observer',
        self::ROLE_ADMIN    => 'Admin',
    ];

    /** Roles a user can be given on one site (admins already see every site). */
    public const SITE_ROLES = [self::ROLE_AGENT, self::ROLE_OBSERVER];

    // Helpers rôles
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
    public function isAgent(): bool
    {
        return $this->role === self::ROLE_AGENT;
    }
    public function isObservateur(): bool
    {
        return $this->role === self::ROLE_OBSERVER;
    }

    /**
     * May this user change things on that site — enter manual readings,
     * switch actuators? Admins always. Otherwise the role the user was given
     * *on that site* (Manage Users) decides; a site's owner with no explicit
     * assignment falls back to the account's own role.
     *
     * Seeing the site at all is a separate question (AuthorizesSiteAccess).
     */
    public function canWriteTo(Site $site): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $siteRole = $this->assignedSites()->where('sites.id', $site->id)->value('site_user.role');

        if ($siteRole !== null) {
            return $siteRole === self::ROLE_AGENT;
        }

        return $site->user_id === $this->id && !$this->isObservateur();
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