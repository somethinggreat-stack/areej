<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $fillable = [
        'order_id', 'amount', 'method', 'kind',
        'paid_on', 'reference', 'recorded_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_on' => 'date',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function amountInPounds(): float
    {
        return $this->amount / 100;
    }

    /** @return array<string, string> */
    public static function methodLabels(): array
    {
        return [
            'cash' => __('Cash'),
            'bank_transfer' => __('Bank transfer'),
            'card' => __('Card'),
            'cheque' => __('Cheque'),
            'other' => __('Other'),
        ];
    }

    /** @return array<string, string> */
    public static function kindLabels(): array
    {
        return [
            'deposit' => __('Deposit'),
            'part_payment' => __('Part payment'),
            'balance' => __('Balance'),
            'refund' => __('Refund'),
        ];
    }

    public function methodLabel(): string
    {
        return self::methodLabels()[$this->method] ?? $this->method;
    }

    public function kindLabel(): string
    {
        return self::kindLabels()[$this->kind] ?? $this->kind;
    }
}
