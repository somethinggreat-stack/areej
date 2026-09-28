<?php

namespace App\Models;

use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'inventory_category_id',
        'supplier_id',
        'name_en',
        'name_ur',
        'sku',
        'unit',
        'current_quantity',
        'reorder_level',
        'reorder_quantity',
        'unit_cost',
        'count_frequency',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_quantity' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'reorder_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<InventoryCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    /** @return HasMany<WasteLog, $this> */
    public function wasteLogs(): HasMany
    {
        return $this->hasMany(WasteLog::class);
    }

    /** @return HasMany<StockCountLine, $this> */
    public function countLines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }

    /** @return HasMany<DishIngredient, $this> */
    public function dishIngredients(): HasMany
    {
        return $this->hasMany(DishIngredient::class);
    }

    /** @return HasMany<PurchaseOrderLine, $this> */
    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /**
     * Items at or below their reorder level. Drives the low-stock alerts the
     * client asked for, grouped by supplier so one order covers the lot.
     *
     * @param  Builder<InventoryItem>  $query
     */
    public function scopeNeedsReorder(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereColumn('current_quantity', '<=', 'reorder_level');
    }

    /**
     * @param  Builder<InventoryItem>  $query
     */
    public function scopeCountedWeekly(Builder $query): void
    {
        $query->where('is_active', true)->where('count_frequency', 'weekly');
    }

    public function isLowStock(): bool
    {
        return (float) $this->current_quantity <= (float) $this->reorder_level;
    }

    /**
     * Value of what is on the shelf right now, at purchase price.
     */
    public function stockValue(): float
    {
        return (float) $this->current_quantity * (float) ($this->unit_cost ?? 0);
    }

    /**
     * Suggested order quantity: whatever was set for the item, otherwise enough
     * to get back above the reorder level with a little headroom.
     */
    public function suggestedOrderQuantity(): float
    {
        if ($this->reorder_quantity !== null && (float) $this->reorder_quantity > 0) {
            return (float) $this->reorder_quantity;
        }

        $shortfall = (float) $this->reorder_level - (float) $this->current_quantity;

        return max(0, round($shortfall + ((float) $this->reorder_level * 0.25), 3));
    }

    public function displayName(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ur'
            ? ($this->name_ur ?: $this->name_en)
            : $this->name_en;
    }
}
