<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A confirmed job.
 *
 * Money is held in pence as integers throughout. The `*InPounds` helpers are
 * the only place the conversion happens, so no controller has to remember it.
 */
class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'enquiry_id', 'customer_name', 'phone', 'email',
        'order_type', 'event_date', 'serve_time', 'venue', 'venue_address',
        'guests', 'service_style', 'staff_required', 'menu_notes', 'dietary',
        'total_amount', 'deposit_due', 'status', 'source', 'taken_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'guests' => 'integer',
            'staff_required' => 'integer',
            'total_amount' => 'integer',
            'deposit_due' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->reference ??= 'ORD-'.strtoupper(Str::random(5));
        });
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('position');
    }

    /** @return HasMany<OrderPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class)->orderBy('paid_on');
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function shifts(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /** @return HasMany<OrderDish, $this> */
    public function dishes(): HasMany
    {
        return $this->hasMany(OrderDish::class);
    }

    /** @return HasMany<OrderTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(OrderTask::class)->orderBy('position');
    }

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** @return HasMany<Quote, $this> */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /** @return HasMany<WasteLog, $this> */
    public function wasteLogs(): HasMany
    {
        return $this->hasMany(WasteLog::class);
    }

    /** @return HasMany<EquipmentAssignment, $this> */
    public function equipment(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    /** @return BelongsTo<Enquiry, $this> */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    /** @return BelongsTo<User, $this> */
    public function takenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by');
    }

    /** @param  Builder<Order>  $query */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereDate('event_date', '>=', today())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('event_date');
    }

    /** @param  Builder<Order>  $query */
    public function scopeOwing(Builder $query): void
    {
        $query->whereNotIn('status', ['cancelled'])->where('total_amount', '>', 0);
    }

    /* ------------------------------------------------------------------ money */

    /** Everything received so far, minus refunds. In pence. */
    public function paidAmount(): int
    {
        return (int) $this->payments->sum(
            fn (OrderPayment $p) => $p->kind === 'refund' ? -$p->amount : $p->amount
        );
    }

    /** In pence. Negative would mean overpaid, so it is floored at zero. */
    public function balanceAmount(): int
    {
        return max(0, (int) $this->total_amount - $this->paidAmount());
    }

    public function totalInPounds(): float
    {
        return $this->total_amount / 100;
    }

    public function paidInPounds(): float
    {
        return $this->paidAmount() / 100;
    }

    public function balanceInPounds(): float
    {
        return $this->balanceAmount() / 100;
    }

    public function isPaid(): bool
    {
        return $this->total_amount > 0 && $this->balanceAmount() === 0;
    }

    /**
     * Derived rather than stored so it can never disagree with the payments.
     */
    public function paymentStatus(): string
    {
        if ($this->total_amount === 0) {
            return 'no_price_set';
        }

        $paid = $this->paidAmount();

        return match (true) {
            $paid <= 0 => 'unpaid',
            $paid >= $this->total_amount => 'paid',
            default => 'part_paid',
        };
    }

    public function paymentStatusLabel(): string
    {
        return [
            'no_price_set' => __('No price set'),
            'unpaid' => __('Unpaid'),
            'part_paid' => __('Part paid'),
            'paid' => __('Paid'),
        ][$this->paymentStatus()];
    }

    /** Sum of the line totals, in pence. Used to keep the header honest. */
    public function itemsTotal(): int
    {
        return (int) $this->items->sum('line_total');
    }

    /* ----------------------------------------------------------------- labels */

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            'draft' => __('Draft'),
            'confirmed' => __('Confirmed'),
            'in_preparation' => __('In preparation'),
            'delivered' => __('Delivered'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
        ];
    }

    /** @return array<string, string> */
    public static function serviceStyleLabels(): array
    {
        return [
            'not_set' => __('Not set'),
            'delivery' => __('Delivery'),
            'collection' => __('Collection'),
            'delivered_and_served' => __('Delivered and served'),
            'full_buffet' => __('Full buffet setup'),
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function serviceStyleLabel(): string
    {
        return self::serviceStyleLabels()[$this->service_style] ?? $this->service_style;
    }

    public function isUrgent(): bool
    {
        return $this->event_date !== null
            && $this->event_date->isBetween(today(), today()->addDays(14));
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'confirmed', 'in_preparation' => 'info',
            'delivered', 'completed' => 'good',
            'cancelled' => 'bad',
            default => 'neutral',
        };
    }

    public function taskProgress(): int
    {
        $total = $this->tasks->count();

        return $total === 0 ? 0 : (int) round($this->tasks->where('is_done', true)->count() / $total * 100);
    }

    /* ------------------------------------------------------------- margin */

    /**
     * Stock movements booked against this job — what the kitchen actually used.
     *
     * @return Builder<StockMovement>
     */
    public function movements()
    {
        return StockMovement::where('source_type', self::class)->where('source_id', $this->getKey());
    }

    /**
     * What the job really cost, in pence.
     *
     * Split rather than a single number because the three levers are different
     * conversations: ingredients is a buying problem, labour is a rostering
     * problem, waste is a portioning problem.
     *
     * @return array{ingredients: int, labour: int, waste: int, other: int, total: int}
     */
    public function costBreakdown(): array
    {
        $ingredients = (int) round(
            $this->movements()->where('type', StockMovement::TYPE_EVENT_USAGE)->get()
                ->sum(fn (StockMovement $m) => $m->value()) * 100
        );

        $labour = (int) round(
            $this->shifts->sum(fn (AttendanceRecord $s) => $s->paidHours() * (float) ($s->hourly_rate ?? 0)) * 100
        );

        $waste = (int) round($this->wasteLogs->sum(fn (WasteLog $w) => $w->cost()) * 100);
        $other = (int) $this->expenses->sum('amount');

        return [
            'ingredients' => $ingredients,
            'labour' => $labour,
            'waste' => $waste,
            'other' => $other,
            'total' => $ingredients + $labour + $waste + $other,
        ];
    }

    /** Revenue less every recorded cost, in pence. */
    public function profit(): int
    {
        return $this->total_amount - $this->costBreakdown()['total'];
    }

    /**
     * Null rather than a number whenever the answer would be a lie.
     *
     * An unpriced job has no margin. A job with no costs recorded yet would
     * report 100%, which reads as "pure profit" when it actually means "nobody
     * has logged what this cost" — the most dangerous number on the screen.
     */
    public function marginPercent(): ?float
    {
        if ($this->total_amount <= 0 || $this->costBreakdown()['total'] <= 0) {
            return null;
        }

        return round($this->profit() / $this->total_amount * 100, 1);
    }

    /** True once there is enough recorded to talk about margin at all. */
    public function hasCostData(): bool
    {
        return $this->costBreakdown()['total'] > 0;
    }
}
