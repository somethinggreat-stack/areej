<?php

namespace App\Models;

use Database\Factories\StaffProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employment record. Casual event staff exist here without ever having a login.
 */
class StaffProfile extends Model
{
    /** @use HasFactory<StaffProfileFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'employment_type',
        'department',
        'hourly_rate',
        'overtime_rate',
        'holiday_allowance_hours',
        'fingerprint_enroll_id',
        'clock_pin',
        'started_on',
        'is_active',
    ];

    /** @var list<string> */
    protected $hidden = ['clock_pin'];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
            'started_on' => 'date',
            'is_active' => 'boolean',
            'clock_pin' => 'hashed',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Event staff work away from Landor Street, so they cannot use the
     * fingerprint terminal and are checked in on the roster at the venue.
     */
    public function clocksInAtVenues(): bool
    {
        return $this->employment_type === 'event_staff';
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /** @return HasMany<OrderTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(OrderTask::class, 'assigned_to');
    }

    /** @return HasMany<WagePayment, $this> */
    public function wagePayments(): HasMany
    {
        return $this->hasMany(WagePayment::class);
    }

    /** @return HasMany<LeaveRequest, $this> */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Holiday hours already approved this leave year, so the remaining balance
     * can be shown without a second query in every view.
     */
    public function holidayHoursTaken(?int $year = null): float
    {
        $year ??= (int) now()->year;

        return (float) $this->leaveRequests()
            ->where('type', 'holiday')
            ->where('status', 'approved')
            ->whereYear('starts_on', $year)
            ->sum('hours');
    }

    public function holidayHoursRemaining(?int $year = null): float
    {
        return round($this->holiday_allowance_hours - $this->holidayHoursTaken($year), 2);
    }

    /** The shift they are currently clocked into, if any. */
    public function openShift(): ?AttendanceRecord
    {
        return $this->attendanceRecords()->open()->latest('clock_in_at')->first();
    }
}
