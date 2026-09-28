<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'phone',
        'locale',
        'is_active',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return HasOne<StaffProfile, $this> */
    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    /**
     * True when the account sits at or above the given role level.
     */
    public function hasRoleLevel(int $level): bool
    {
        return ($this->role?->level ?? 0) >= $level;
    }

    /**
     * Pay rates, wage bills and event margins are restricted to management and
     * the owner. Kitchen managers and staff must never see them.
     */
    public function canSeeFinancials(): bool
    {
        return $this->hasRoleLevel(80);
    }

    public function canManageInventory(): bool
    {
        return $this->hasRoleLevel(50);
    }

    public function isOwner(): bool
    {
        return $this->role?->slug === Role::OWNER;
    }
}
