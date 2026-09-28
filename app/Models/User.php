<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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

    /**
     * Quotes are prices by nature, so they belong to the people who price
     * work: sales, finance, management and the owner. The kitchen manager and
     * purchasing sit higher on the ladder but never see what a job is sold for.
     */
    public function canHandleQuotes(): bool
    {
        return $this->canSeeFinancials()
            || in_array($this->role?->slug, [Role::SALES, Role::FINANCE], true);
    }

    /**
     * Management can look after anyone below management; only the owner can
     * change another manager or owner. Nobody manages their own login here —
     * that is what the account page is for.
     */
    public function canManageLogin(User $other): bool
    {
        if (! $this->hasRoleLevel(80) || $this->is($other)) {
            return false;
        }

        return $this->isOwner() || ($other->role?->level ?? 0) < 80;
    }

    /**
     * The roles this person may hand out: the owner any, management only
     * those below management.
     *
     * @return Collection<int, Role>
     */
    public function assignableRoles(): Collection
    {
        return Role::query()
            ->when(! $this->isOwner(), fn ($query) => $query->where('level', '<', 80))
            ->orderByDesc('level')
            ->get();
    }

    public function isOwner(): bool
    {
        return $this->role?->slug === Role::OWNER;
    }
}
