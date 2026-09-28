<?php

namespace App\Models;

use Database\Factories\InventoryCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCategory extends Model
{
    /** @use HasFactory<InventoryCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ur',
        'slug',
        'kind',
        'sort_order',
    ];

    /** @return HasMany<InventoryItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Equipment leaves the unit and is expected back; everything else is
     * consumed. That distinction drives the check-out/check-in tracking.
     */
    public function isReturnable(): bool
    {
        return $this->kind === 'equipment';
    }

    /**
     * Urdu is the client's working language, so names follow the interface.
     * Falls back to English where no Urdu name has been entered.
     */
    public function displayName(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ur'
            ? ($this->name_ur ?: $this->name_en)
            : $this->name_en;
    }
}
