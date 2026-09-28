<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A priced offer. Accepting one is what creates an order.
 */
class Quote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'enquiry_id', 'order_id', 'customer_name', 'phone', 'email',
        'event_type', 'event_date', 'venue', 'guests', 'service_style',
        'subtotal', 'discount_percent', 'total', 'status', 'valid_until',
        'sent_at', 'decided_at', 'terms', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'decided_at' => 'datetime',
            'guests' => 'integer',
            'subtotal' => 'integer',
            'discount_percent' => 'integer',
            'total' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote): void {
            $quote->reference ??= 'QT-'.strtoupper(Str::random(5));
        });
    }

    /** @return HasMany<QuoteLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('position');
    }

    /** @return BelongsTo<Enquiry, $this> */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param  Builder<Quote>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', ['draft', 'sent']);
    }

    /** @param  Builder<Quote>  $query */
    public function scopeAwaitingReply(Builder $query): void
    {
        $query->where('status', 'sent');
    }

    /**
     * Recalculates from the lines. Called after any line change so the header
     * can never disagree with what is listed underneath it.
     */
    public function recalculate(): void
    {
        $subtotal = (int) $this->lines()->sum('line_total');
        $discount = (int) round($subtotal * max(0, min(100, $this->discount_percent)) / 100);

        $this->forceFill([
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $discount),
        ])->save();
    }

    public function discountAmount(): int
    {
        return max(0, $this->subtotal - $this->total);
    }

    public function totalInPounds(): float
    {
        return $this->total / 100;
    }

    public function perHeadInPounds(): float
    {
        return $this->guests > 0 ? $this->total / 100 / $this->guests : 0.0;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent'], true);
    }

    public function isExpired(): bool
    {
        return $this->status === 'sent'
            && $this->valid_until !== null
            && $this->valid_until->isPast();
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            'draft' => __('Draft'),
            'sent' => __('Awaiting reply'),
            'accepted' => __('Accepted'),
            'declined' => __('Declined'),
            'expired' => __('Expired'),
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'accepted' => 'good',
            'declined', 'expired' => 'bad',
            'sent' => 'info',
            default => 'neutral',
        };
    }
}
