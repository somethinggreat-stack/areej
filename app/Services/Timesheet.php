<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Setting;
use App\Models\StaffProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Weekly hours and pay.
 *
 * The client pays weekly, so a week here always runs Monday to Sunday. Overtime
 * is applied per week rather than per shift — someone working four twelve-hour
 * days is on overtime by the fourth, which a per-shift rule would miss.
 */
class Timesheet
{
    /** Hours past which the overtime rate applies, per week, unless Settings says otherwise. */
    public const OVERTIME_AFTER_HOURS = 40;

    public function overtimeAfterHours(): int
    {
        return (int) Setting::get('overtime_after_hours', self::OVERTIME_AFTER_HOURS);
    }

    public function weekStart(?CarbonImmutable $anyDayInWeek = null): CarbonImmutable
    {
        return ($anyDayInWeek ?? CarbonImmutable::today())->startOfWeek(CarbonImmutable::MONDAY);
    }

    /**
     * One person's week.
     *
     * @return array{
     *     staff: StaffProfile,
     *     records: Collection<int, AttendanceRecord>,
     *     total_hours: float,
     *     normal_hours: float,
     *     overtime_hours: float,
     *     normal_pay: float,
     *     overtime_pay: float,
     *     total_pay: float,
     *     absences: int,
     *     late_shifts: int
     * }
     */
    public function forStaff(StaffProfile $staff, CarbonImmutable $weekStart): array
    {
        $records = $staff->attendanceRecords()
            ->whereBetween('worked_on', [$weekStart, $weekStart->addDays(6)])
            ->orderBy('worked_on')
            ->orderBy('clock_in_at')
            ->get();

        $payable = $records->filter(
            fn (AttendanceRecord $r) => in_array($r->status, ['closed', 'approved'], true) && $r->clock_out_at !== null
        );

        $totalHours = round($payable->sum(fn (AttendanceRecord $r) => $r->paidHours()), 2);

        // Cast explicitly: min() against the int constant would hand back an
        // int the moment the cap bites, so the shape of this array would change
        // depending on how much somebody worked.
        $overtimeAfter = $this->overtimeAfterHours();
        $normalHours = (float) min($totalHours, $overtimeAfter);
        $overtimeHours = (float) round(max(0, $totalHours - $overtimeAfter), 2);

        // Prefer the rate frozen on the shift; fall back to the profile for
        // shifts that have not been approved yet.
        $rate = (float) ($payable->first()?->hourly_rate ?? $staff->hourly_rate ?? 0);
        $overtimeRate = (float) ($payable->first()?->overtime_rate ?? $staff->overtime_rate ?? $rate * 1.5);

        return [
            'staff' => $staff,
            'records' => $records,
            'total_hours' => $totalHours,
            'normal_hours' => $normalHours,
            'overtime_hours' => $overtimeHours,
            'normal_pay' => round($normalHours * $rate, 2),
            'overtime_pay' => round($overtimeHours * $overtimeRate, 2),
            'total_pay' => round(($normalHours * $rate) + ($overtimeHours * $overtimeRate), 2),
            'absences' => $records->filter(fn (AttendanceRecord $r) => $r->isAbsent())->count(),
            'late_shifts' => $records->filter(fn (AttendanceRecord $r) => $r->isLate())->count(),
        ];
    }

    /**
     * The whole week's pay run, one row per active member of staff who has
     * anything recorded.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function payRun(CarbonImmutable $weekStart): Collection
    {
        return StaffProfile::where('is_active', true)
            ->orderBy('full_name')
            ->get()
            ->map(fn (StaffProfile $staff) => $this->forStaff($staff, $weekStart))
            ->filter(fn (array $row) => $row['records']->isNotEmpty())
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $payRun
     * @return array{hours: float, pay: float, people: int, absences: int}
     */
    public function payRunTotals(Collection $payRun): array
    {
        return [
            'hours' => round($payRun->sum('total_hours'), 2),
            'pay' => round($payRun->sum('total_pay'), 2),
            'people' => $payRun->count(),
            'absences' => (int) $payRun->sum('absences'),
        ];
    }
}
