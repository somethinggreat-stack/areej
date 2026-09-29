<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\StaffProfile;
use App\Services\Activity;
use App\Services\Timesheet;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The people. Rates and holiday balances live here, which is why the whole
 * area sits behind management level.
 */
class StaffController extends Controller
{
    public function __construct(
        private readonly Activity $activity,
        private readonly Timesheet $timesheet,
    ) {}

    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();

        $staff = StaffProfile::query()
            ->with('user')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($q) => $q->where('full_name', 'like', $term)->orWhere('phone', 'like', $term));
            })
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->string('department')->toString()))
            ->when($filter !== 'archived', fn ($q) => $q->where('is_active', true))
            ->when($filter === 'archived', fn ($q) => $q->where('is_active', false))
            ->orderBy('full_name')
            ->paginate(30)
            ->withQueryString();

        return view('dashboard.staff.index', [
            'staff' => $staff,
            'filter' => $filter,
            'pendingLeave' => LeaveRequest::pending()->with('staff')->get(),
            'counts' => [
                'total' => StaffProfile::where('is_active', true)->count(),
                'full_time' => StaffProfile::where('is_active', true)->where('employment_type', 'full_time')->count(),
                'event' => StaffProfile::where('is_active', true)->where('employment_type', 'event_staff')->count(),
            ],
        ]);
    }

    public function show(StaffProfile $staffProfile): View
    {
        $week = $this->timesheet->weekStart(CarbonImmutable::today());

        return view('dashboard.staff.show', [
            'staff' => $staffProfile->load('user'),
            'week' => $this->timesheet->forStaff($staffProfile, $week),
            'weekStart' => $week,
            'leave' => $staffProfile->leaveRequests()->latest('starts_on')->limit(20)->get(),
            'wages' => $staffProfile->wagePayments()->latest('week_start')->latest('id')->limit(20)->get(),
            'recent' => $staffProfile->attendanceRecords()
                ->with('order')
                ->latest('worked_on')
                ->limit(20)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $staff = StaffProfile::create($this->validated($request));

        $this->activity->created($staff, __('Staff added: :name', ['name' => $staff->full_name]));

        return redirect()->route('staff.show', $staff)
            ->with('status', __(':name added.', ['name' => $staff->full_name]));
    }

    public function update(Request $request, StaffProfile $staffProfile): RedirectResponse
    {
        $before = $staffProfile->getOriginal();
        $staffProfile->update($this->validated($request));

        $this->activity->updated($staffProfile, __('Staff updated: :name', ['name' => $staffProfile->full_name]), $before);

        return back()->with('status', __('Saved.'));
    }

    public function destroy(StaffProfile $staffProfile): RedirectResponse
    {
        // Shifts already worked are pay records; the person is archived rather
        // than removed so the history behind those hours survives.
        $name = $staffProfile->full_name;

        $staffProfile->update(['is_active' => false]);
        $staffProfile->delete();

        // Someone who has left should not keep a way in: their login goes off
        // too, and the next page they open signs them out.
        $staffProfile->user?->update(['is_active' => false]);

        $this->activity->deleted($staffProfile, __('Staff archived: :name', ['name' => $name]));

        return redirect()->route('staff')->with('status', __(':name archived. Their worked shifts are kept.', ['name' => $name]));
    }

    public function storeLeave(Request $request, StaffProfile $staffProfile): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:holiday,sick,unpaid,other'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'hours' => ['required', 'numeric', 'gt:0', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['type'] === 'holiday') {
            $remaining = $staffProfile->holidayHoursRemaining();

            if ($data['hours'] > $remaining) {
                return back()->withErrors([
                    'hours' => __(':name has :n hours of holiday left this year.', [
                        'name' => $staffProfile->full_name,
                        'n' => number_format(max(0, $remaining), 1),
                    ]),
                ])->withInput();
            }
        }

        $staffProfile->leaveRequests()->create([
            ...$data,
            'status' => 'pending',
            'requested_by' => $request->user()->id,
        ]);

        return back()->with('status', __('Leave request added.'));
    }

    public function decideLeave(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'decision_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $leaveRequest->update([
            'status' => $data['decision'],
            'decision_notes' => $data['decision_notes'] ?? null,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $this->activity->log(
            $data['decision'],
            __('Leave :decision for :name', [
                'decision' => $data['decision'],
                'name' => $leaveRequest->staff->full_name,
            ]),
            $leaveRequest,
        );

        return back()->with('status', $data['decision'] === 'approved'
            ? __('Leave approved.')
            : __('Leave rejected.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'employment_type' => ['nullable', 'in:full_time,part_time,event_staff'],
            'department' => ['nullable', 'in:kitchen,service,delivery,management'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'holiday_allowance_hours' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'started_on' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        // Adding someone needs only a name: the rest has sensible defaults so
        // the form can stay three boxes long.
        $data['employment_type'] ??= 'full_time';
        $data['department'] ??= 'kitchen';
        $data['holiday_allowance_hours'] ??= 0;

        return $data;
    }
}
