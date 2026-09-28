<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDish extends Model
{
    protected $fillable = ['order_id', 'dish_id', 'guests', 'ingredients_deducted', 'notes'];

    protected function casts(): array
    {
        return [
            'guests' => 'integer',
            'ingredients_deducted' => 'boolean',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Dish, $this> */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}
