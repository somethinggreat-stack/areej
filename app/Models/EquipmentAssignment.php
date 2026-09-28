<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentAssignment extends Model
{
    protected $fillable = [
        'equipment_item_id', 'order_id', 'quantity_out', 'quantity_returned',
        'quantity_lost', 'quantity_damaged', 'checked_out_at', 'returned_at',
        'checked_out_by', 'returned_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_out' => 'integer',
            'quantity_returned' => 'integer',
            'quantity_lost' => 'integer',
            'quantity_damaged' => 'integer',
            'checked_out_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<EquipmentItem, $this> */
    public function equipmentItem(): BelongsTo
    {
        return $this->belongsTo(EquipmentItem::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @param  Builder<EquipmentAssignment>  $query */
    public function scopeStillOut(Builder $query): void
    {
        $query->whereNull('returned_at');
    }

    public function unaccountedFor(): int
    {
        return max(0, $this->quantity_out - $this->quantity_returned - $this->quantity_lost - $this->quantity_damaged);
    }

    /** Replacement value of what did not come back. In pence. */
    public function lossValue(): int
    {
        return ($this->quantity_lost + $this->quantity_damaged)
            * ($this->equipmentItem?->replacement_cost ?? 0);
    }
}
