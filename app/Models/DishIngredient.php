<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishIngredient extends Model
{
    protected $fillable = ['dish_id', 'inventory_item_id', 'quantity_per_100', 'notes'];

    protected function casts(): array
    {
        return ['quantity_per_100' => 'decimal:3'];
    }

    /** @return BelongsTo<Dish, $this> */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** How much this ingredient is needed for a given head count. */
    public function quantityFor(int $guests): float
    {
        return round((float) $this->quantity_per_100 * $guests / 100, 3);
    }
}
