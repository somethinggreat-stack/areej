<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One shift — rostered, worked, or both.
 *
 * Pay is worked out from `paidHours()` against the rate frozen on the row, so a
 * rise in someone's hourly rate never rewrites a week that has been paid.
 */
class AttendanceRecord extends Model
{
    protected $fillable = [
        'staff_profile_id', 'order_id', 'worked_on',
        'scheduled_start_at', 'scheduled_end_at',
        'clock_in_at', 'clock_out_at', 'break_minutes',
        'method', 'status', 'latitude', 'longitude',
        'hourly_rate', 'overtime_rate',
        'recorded_by', 'approved_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'break_minutes' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'hourly_rate' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<StaffProfile, $this> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'staff_profile_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @param  Builder<AttendanceRecord>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', 'open')->whereNotNull('clock_in_at')->whereNull('clock_out_at');
    }

    /** @param  Builder<AttendanceRecord>  $query */
    public function scopeForWeek(Builder $query, $monday): void
    {
        $query->whereBetween('worked_on', [$monday, $monday->copy()->addDays(6)]);
    }

    /** @param  Builder<AttendanceRecord>  $query */
    public function scopePayable(Builder $query): void
    {
        $query->whereIn('status', ['closed', 'approved'])->whereNotNull('clock_out_at');
    }

    /* ------------------------------------------------------------------ hours */

    /** Worked time less unpaid breaks, in hours. */
    public function paidHours(): float
    {
        if ($this->clock_in_at === null || $this->clock_out_at === null) {
            return 0.0;
        }

        $minutes = $this->clock_in_at->diffInMinutes($this->clock_out_at) - $this->break_minutes;

        return max(0, round($minutes / 60, 2));
    }

    /** Minutes past the rostered start. Null when there was no roster. */
    public function minutesLate(): ?int
    {
        if ($this->scheduled_start_at === null || $this->clock_in_at === null) {
            return null;
        }

        $late = $this->scheduled_start_at->diffInMinutes($this->clock_in_at, false);

        return $late > 0 ? (int) $late : 0;
    }

    public function isLate(int $graceMinutes = 5): bool
    {
        return ($this->minutesLate() ?? 0) > $graceMinutes;
    }

    public function isAbsent(): bool
    {
        return $this->status === 'absent';
    }

    public function isOpen(): bool
    {
        return $this->clock_in_at !== null && $this->clock_out_at === null;
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            'scheduled' => __('Rostered'),
            'open' => __('Clocked in'),
            'closed' => __('Worked'),
            'approved' => __('Approved'),
            'absent' => __('Absent'),
        ];
    }

    /** @return array<string, string> */
    public static function methodLabels(): array
    {
        return [
            'terminal' => __('Fingerprint terminal'),
            'roster' => __('Venue check-in'),
            'manual' => __('Entered by hand'),
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function methodLabel(): string
    {
        return self::methodLabels()[$this->method] ?? $this->method;
    }
}
