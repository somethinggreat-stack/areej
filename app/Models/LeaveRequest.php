<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'staff_profile_id', 'type', 'starts_on', 'ends_on', 'hours',
        'status', 'requested_by', 'decided_by', 'decided_at',
        'reason', 'decision_notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'hours' => 'decimal:2',
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<StaffProfile, $this> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'staff_profile_id');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @param  Builder<LeaveRequest>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending')->orderBy('starts_on');
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            'holiday' => __('Holiday'),
            'sick' => __('Sick leave'),
            'unpaid' => __('Unpaid leave'),
            'other' => __('Other'),
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }

    /** Only holiday counts against the allowance. */
    public function countsAgainstAllowance(): bool
    {
        return $this->type === 'holiday' && $this->status === 'approved';
    }
}
