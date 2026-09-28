<?php

namespace App\Models;

use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'reference',
        'name',
        'phone',
        'email',
        'event_type',
        'event_date',
        'guests',
        'venue',
        'service_style',
        'extras',
        'dietary',
        'message',
        'status',
        'assigned_to',
        'internal_notes',
        'source',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'extras' => 'array',
            'event_date' => 'date',
            'guests' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Enquiry $enquiry): void {
            $enquiry->reference ??= 'MC-'.strtoupper(Str::random(6));
        });
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasOne<Order, $this> */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    /** @return HasMany<Quote, $this> */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->latest();
    }

    /** @param  Builder<Enquiry>  $query */
    public function scopeNeedsAttention(Builder $query): void
    {
        $query->where('status', 'new')->orderBy('created_at');
    }

    /**
     * Anything inside a fortnight is urgent for a caterer — the food has to be
     * ordered and the staff rostered.
     */
    public function isUrgent(): bool
    {
        return $this->event_date !== null
            && $this->event_date->isBefore(now()->addWeeks(2));
    }
}
