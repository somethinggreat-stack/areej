<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Timesheet;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A member of staff's own week: shifts, hours and holiday left.
 *
 * Hours only, never pay. Rates and wage figures stay with management, and this
 * page is open to every login, so it shows nothing a person could not already
 * work out from their own shifts.
 */
class MyTimesheetController extends Controller
{
    public function __construct(private readonly Timesheet $timesheet) {}

    public function index(Request $request): View
    {
        $staff = $request->user()->staffProfile;

        $week = $this->timesheet->weekStart(
            $request->filled('week') ? CarbonImmutable::parse($request->query('week')) : null
        );

        return view('dashboard.my-timesheet.index', [
            'staff' => $staff,
            'weekStart' => $week,
            'week' => $staff ? $this->timesheet->forStaff($staff, $week) : null,
            'leave' => $staff?->leaveRequests()->latest('starts_on')->limit(10)->get() ?? collect(),
        ]);
    }
}
