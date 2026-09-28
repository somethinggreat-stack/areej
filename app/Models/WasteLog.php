<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WasteLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'inventory_item_id', 'order_id', 'quantity', 'reason',
        'unit_cost', 'wasted_on', 'logged_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'wasted_on' => 'date',
        ];
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    /** @param  Builder<WasteLog>  $query */
    public function scopeBetween(Builder $query, $from, $to): void
    {
        $query->whereBetween('wasted_on', [$from, $to]);
    }

    public function cost(): float
    {
        return (float) $this->quantity * (float) ($this->unit_cost ?? 0);
    }

    /** @return array<string, string> */
    public static function reasonLabels(): array
    {
        return [
            'spoilage' => __('Spoilage'),
            'over_production' => __('Over-production'),
            'returned' => __('Returned from a job'),
            'damaged' => __('Damaged'),
            'other' => __('Other'),
        ];
    }

    public function reasonLabel(): string
    {
        return self::reasonLabels()[$this->reason] ?? $this->reason;
    }
}
