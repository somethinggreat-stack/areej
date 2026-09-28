<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Access levels, highest number wins.
 *
 * The client confirmed attendance and pay data is for management and Areej
 * only, so anything below Manager must never see rates, wages or margins.
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const OWNER = 'owner';

    public const MANAGEMENT = 'management';

    public const MANAGER = 'manager';

    public const STAFF = 'staff';

    protected $fillable = [
        'slug',
        'name_en',
        'name_ur',
        'level',
        'abilities',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'level' => 'integer',
        ];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
