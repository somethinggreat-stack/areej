<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'supplier_id', 'status', 'ordered_on', 'expected_on',
        'received_on', 'created_by', 'received_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'ordered_on' => 'date',
            'expected_on' => 'date',
            'received_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $order): void {
            $order->reference ??= 'PO-'.strtoupper(Str::random(6));
        });
    }

    /** @return HasMany<PurchaseOrderLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param  Builder<PurchaseOrder>  $query */
    public function scopeOutstanding(Builder $query): void
    {
        $query->whereIn('status', ['draft', 'sent', 'part_received']);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'part_received'], true);
    }

    /** Total cost in pounds, at the line costs entered. */
    public function total(): float
    {
        return $this->lines->sum(fn (PurchaseOrderLine $line) => $line->lineTotal());
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            'draft' => __('Draft'),
            'sent' => __('Sent to supplier'),
            'part_received' => __('Part received'),
            'received' => __('Received'),
            'cancelled' => __('Cancelled'),
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
