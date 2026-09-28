<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLine extends Model
{
    protected $fillable = [
        'quote_id', 'dish_id', 'description', 'quantity',
        'unit', 'unit_price', 'line_total', 'position',
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
        // The total is stored but never typed: it is always quantity × price.
        static::saving(function (QuoteLine $line): void {
            $line->line_total = (int) round((float) $line->quantity * (int) $line->unit_price);
        });
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return BelongsTo<Dish, $this> */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    public function lineTotalInPounds(): float
    {
        return $this->line_total / 100;
    }
}
