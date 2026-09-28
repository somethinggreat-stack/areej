<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\Timesheet;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Monday pay run. Weekly, per the client.
 */
class TimesheetController extends Controller
{
    public function __construct(private readonly Timesheet $timesheet) {}

    public function index(Request $request): View
    {
        $week = $this->timesheet->weekStart(
            $request->filled('week')
                ? CarbonImmutable::parse($request->string('week')->toString())
                : null
        );

        $payRun = $this->timesheet->payRun($week);

        return view('dashboard.timesheets.index', [
            'week' => $week,
            'payRun' => $payRun,
            'totals' => $this->timesheet->payRunTotals($payRun),
            'overtimeAfter' => Timesheet::OVERTIME_AFTER_HOURS,
            'unapproved' => AttendanceRecord::whereBetween('worked_on', [$week, $week->addDays(6)])
                ->where('status', 'closed')
                ->count(),
        ]);
    }

    /**
     * Signs off a week. Rates are copied onto each shift at this point so a
     * later pay rise cannot rewrite a week that has already been paid.
     */
    public function approve(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'week' => ['required', 'date'],
        ]);

        $week = $this->timesheet->weekStart(CarbonImmutable::parse($data['week']));

        $records = AttendanceRecord::with('staff')
            ->whereBetween('worked_on', [$week, $week->addDays(6)])
            ->where('status', 'closed')
            ->get();

        foreach ($records as $record) {
            $record->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'hourly_rate' => $record->hourly_rate ?? $record->staff->hourly_rate,
                'overtime_rate' => $record->overtime_rate ?? $record->staff->overtime_rate,
            ]);
        }

        return back()->with('status', trans_choice(
            '{0}There was nothing waiting to be approved.|{1}1 shift approved.|[2,*]:count shifts approved.',
            $records->count(),
            ['count' => $records->count()],
        ));
    }
}
