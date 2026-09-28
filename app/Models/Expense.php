<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id', 'supplier_id', 'description', 'category', 'amount',
        'method', 'spent_on', 'reference', 'recorded_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'spent_on' => 'date'];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @param  Builder<Expense>  $query */
    public function scopeBetween(Builder $query, $from, $to): void
    {
        $query->whereBetween('spent_on', [$from, $to]);
    }

    public function amountInPounds(): float
    {
        return $this->amount / 100;
    }

    /** @return array<string, string> */
    public static function categoryLabels(): array
    {
        return [
            'ingredients' => __('Ingredients'),
            'packaging' => __('Packaging'),
            'fuel' => __('Fuel'),
            'vehicle' => __('Vehicle'),
            'equipment_hire' => __('Equipment hire'),
            'venue' => __('Venue'),
            'wages' => __('Wages'),
            'utilities' => __('Utilities'),
            'rent' => __('Rent'),
            'repairs' => __('Repairs'),
            'other' => __('Other'),
        ];
    }

    public function categoryLabel(): string
    {
        return self::categoryLabels()[$this->category] ?? $this->category;
    }
}
