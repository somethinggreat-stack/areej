<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name_en', 'name_ur', 'category', 'asset_tag',
        'quantity_owned', 'replacement_cost', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_owned' => 'integer',
            'replacement_cost' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<EquipmentAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    /** @return HasMany<EquipmentMaintenance, $this> */
    public function maintenance(): HasMany
    {
        return $this->hasMany(EquipmentMaintenance::class)->latest();
    }

    /** Units currently away being fixed, and so not sendable. */
    public function quantityInMaintenance(): int
    {
        return (int) $this->maintenance()
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->sum('quantity');
    }

    /**
     * Out on jobs right now. Derived from open assignments so it cannot drift
     * away from what the assignments actually say.
     */
    public function quantityOut(): int
    {
        return (int) $this->assignments()
            ->whereNull('returned_at')
            ->sum('quantity_out');
    }

    public function quantityAvailable(): int
    {
        return max(0, $this->quantity_owned - $this->quantityOut());
    }

    public function displayName(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ur'
            ? ($this->name_ur ?: $this->name_en)
            : $this->name_en;
    }
}
