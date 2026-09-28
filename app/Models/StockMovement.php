<?php

namespace App\Models;

use App\Services\StockLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A single line of the stock ledger. Written only by {@see StockLedger}
 * so that no code path can change a quantity without leaving a trail.
 */
class StockMovement extends Model
{
    public const TYPE_COUNT = 'count_adjustment';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_WASTE = 'waste';

    public const TYPE_EVENT_USAGE = 'event_usage';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_OPENING = 'opening_balance';

    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity_change',
        'quantity_after',
        'unit_cost',
        'source_type',
        'source_id',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'decimal:3',
            'quantity_after' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return MorphTo<Model, $this> */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /** @param  Builder<StockMovement>  $query */
    public function scopeIncoming(Builder $query): void
    {
        $query->where('quantity_change', '>', 0);
    }

    /** @param  Builder<StockMovement>  $query */
    public function scopeOutgoing(Builder $query): void
    {
        $query->where('quantity_change', '<', 0);
    }

    /**
     * What this movement was worth, at the cost captured when it happened.
     */
    public function value(): float
    {
        return abs((float) $this->quantity_change) * (float) ($this->unit_cost ?? 0);
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_COUNT => __('Count adjustment'),
            self::TYPE_PURCHASE => __('Delivery received'),
            self::TYPE_WASTE => __('Waste'),
            self::TYPE_EVENT_USAGE => __('Used on a job'),
            self::TYPE_MANUAL => __('Manual adjustment'),
            self::TYPE_OPENING => __('Opening balance'),
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }
}
