<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A counting session. Stays a draft until it is submitted, at which point every
 * counted line is pushed through the stock ledger in one go.
 */
class StockCount extends Model
{
    protected $fillable = [
        'reference', 'counted_on', 'scope', 'status',
        'opened_by', 'completed_by', 'completed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'counted_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StockCount $count): void {
            $count->reference ??= 'SC-'.strtoupper(Str::random(6));
        });
    }

    /** @return HasMany<StockCountLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }

    /** @return BelongsTo<User, $this> */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** @param  Builder<StockCount>  $query */
    public function scopeDraft(Builder $query): void
    {
        $query->where('status', 'draft');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function countedLines(): int
    {
        return $this->lines->whereNotNull('counted_quantity')->count();
    }

    public function progress(): int
    {
        $total = $this->lines->count();

        return $total === 0 ? 0 : (int) round($this->countedLines() / $total * 100);
    }
}
