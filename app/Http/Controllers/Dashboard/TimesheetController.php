<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\WagePayment;
use App\Services\Activity;
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
    public function __construct(
        private readonly Timesheet $timesheet,
        private readonly Activity $activity,
    ) {}

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
            'payments' => WagePayment::whereDate('week_start', $week->toDateString())
                ->get()
                ->groupBy('staff_profile_id'),
            'totals' => $this->timesheet->payRunTotals($payRun),
            'overtimeAfter' => $this->timesheet->overtimeAfterHours(),
            'unapproved' => AttendanceRecord::whereBetween('worked_on', [$week, $week->addDays(6)])
                ->where('status', 'closed')
                ->count(),
        ]);
    }

    /**
     * Records wages handed over for a week — usually cash, one click per person.
     */
    public function pay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'staff_profile_id' => ['required', 'exists:staff_profiles,id'],
            'week' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'method' => ['nullable', 'in:cash,bank_transfer,other'],
            'paid_on' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $payment = WagePayment::create([
            'staff_profile_id' => $data['staff_profile_id'],
            'week_start' => $this->timesheet->weekStart(CarbonImmutable::parse($data['week']))->toDateString(),
            'amount' => (int) round($data['amount'] * 100),
            'method' => $data['method'] ?? 'cash',
            'paid_on' => $data['paid_on'] ?? today()->toDateString(),
            'recorded_by' => $request->user()->id,
        ]);

        $this->activity->created($payment, __('Wages paid to :name: :amount', [
            'name' => $payment->staff->full_name,
            'amount' => money($payment->amount),
        ]));

        return back()->with('status', __(':amount paid to :name recorded.', [
            'amount' => money($payment->amount),
            'name' => $payment->staff->full_name,
        ]));
    }

    public function destroyPayment(WagePayment $wagePayment): RedirectResponse
    {
        $name = $wagePayment->staff->full_name;
        $amount = money($wagePayment->amount);
        $wagePayment->delete();

        $this->activity->deleted($wagePayment, __('Wage payment removed: :name :amount', ['name' => $name, 'amount' => $amount]));

        return back()->with('status', __('Payment removed.'));
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
