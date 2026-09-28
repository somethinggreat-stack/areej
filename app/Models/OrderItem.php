<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'inventory_item_id', 'description',
        'quantity', 'unit', 'unit_price', 'line_total', 'position',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'integer',
            'line_total' => 'integer',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // The line total is stored, but it should never be typed in — it is
        // always quantity times price unless somebody deliberately overrides it.
        static::saving(function (OrderItem $item): void {
            if ($item->isDirty(['quantity', 'unit_price']) && ! $item->isDirty('line_total')) {
                $item->line_total = (int) round((float) $item->quantity * (int) $item->unit_price);
            }
        });
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unitPriceInPounds(): float
    {
        return $this->unit_price / 100;
    }

    public function lineTotalInPounds(): float
    {
        return $this->line_total / 100;
    }
}
