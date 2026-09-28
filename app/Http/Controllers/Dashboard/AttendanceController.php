<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Order;
use App\Models\StaffProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Rostering and clocking.
 *
 * The client has 40–50 staff between the kitchen and the vans, so the day view
 * is the working screen: who is on today, who has clocked in, who has not
 * turned up. Venue check-ins come through here too — a supervisor opens the
 * job on their phone and taps names, which is why `clockIn` accepts a location
 * and needs nothing installed on the staff member's own phone.
 */
class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $day = $request->date('day') ?? today();

        $records = AttendanceRecord::with(['staff', 'order'])
            ->whereDate('worked_on', $day)
            ->get()
            ->sortBy(fn (AttendanceRecord $r) => [$r->order?->venue ?? '', $r->staff->full_name]);

        return view('dashboard.attendance.index', [
            'day' => $day,
            'records' => $records,
            'onSite' => $records->filter(fn (AttendanceRecord $r) => $r->isOpen())->count(),
            'absent' => $records->filter(fn (AttendanceRecord $r) => $r->isAbsent())->count(),
            'late' => $records->filter(fn (AttendanceRecord $r) => $r->isLate())->count(),
            'staff' => StaffProfile::where('is_active', true)->orderBy('full_name')->get(),
            'orders' => Order::whereDate('event_date', $day)->orderBy('serve_time')->get(),
            'stillOpen' => AttendanceRecord::open()->with('staff')->get(),
        ]);
    }

    /**
     * Put people on a job. Creates the rostered shifts that later get clocked
     * into — without these, "late" and "absent" have nothing to measure against.
     */
    public function roster(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['nullable', 'exists:orders,id'],
            'worked_on' => ['required', 'date'],
            'scheduled_start' => ['required', 'date_format:H:i'],
            'scheduled_end' => ['nullable', 'date_format:H:i', 'after:scheduled_start'],
            'staff_profile_ids' => ['required', 'array', 'min:1'],
            'staff_profile_ids.*' => ['exists:staff_profiles,id'],
        ]);

        $day = $data['worked_on'];
        $added = 0;

        DB::transaction(function () use ($data, $day, $request, &$added): void {
            foreach ($data['staff_profile_ids'] as $staffId) {
                $exists = AttendanceRecord::where('staff_profile_id', $staffId)
                    ->whereDate('worked_on', $day)
                    ->when($data['order_id'] ?? null, fn ($q, $id) => $q->where('order_id', $id))
                    ->exists();

                if ($exists) {
                    continue;
                }

                $staff = StaffProfile::find($staffId);

                AttendanceRecord::create([
                    'staff_profile_id' => $staffId,
                    'order_id' => $data['order_id'] ?? null,
                    'worked_on' => $day,
                    'scheduled_start_at' => $day.' '.$data['scheduled_start'],
                    'scheduled_end_at' => isset($data['scheduled_end']) ? $day.' '.$data['scheduled_end'] : null,
                    'status' => 'scheduled',
                    'method' => 'roster',
                    'hourly_rate' => $staff?->hourly_rate,
                    'overtime_rate' => $staff?->overtime_rate,
                    'recorded_by' => $request->user()->id,
                ]);

                $added++;
            }
        });

        return back()->with('status', trans_choice(
            '{0}Everybody chosen was already rostered.|{1}1 person rostered.|[2,*]:count people rostered.',
            $added,
            ['count' => $added],
        ));
    }

    public function clockIn(Request $request, AttendanceRecord $record): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if ($record->clock_in_at !== null) {
            return back()->withErrors(['clock' => __(':name is already clocked in.', ['name' => $record->staff->full_name])]);
        }

        $record->update([
            'clock_in_at' => now(),
            'status' => 'open',
            'method' => $record->order_id !== null ? 'roster' : $record->method,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        return back()->with('status', __(':name clocked in.', ['name' => $record->staff->full_name]));
    }

    public function clockOut(Request $request, AttendanceRecord $record): RedirectResponse
    {
        $data = $request->validate([
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
        ]);

        if ($record->clock_in_at === null) {
            return back()->withErrors(['clock' => __('That shift has no clock-in to close.')]);
        }

        $record->update([
            'clock_out_at' => now(),
            'break_minutes' => $data['break_minutes'] ?? $record->break_minutes,
            'status' => 'closed',
        ]);

        return back()->with('status', __(':name clocked out — :hours hours.', [
            'name' => $record->staff->full_name,
            'hours' => number_format($record->paidHours(), 2),
        ]));
    }

    public function markAbsent(AttendanceRecord $record): RedirectResponse
    {
        $record->update(['status' => 'absent', 'clock_in_at' => null, 'clock_out_at' => null]);

        return back()->with('status', __(':name marked absent.', ['name' => $record->staff->full_name]));
    }

    /**
     * Only a shift nobody has worked can be removed; a worked shift is a pay
     * record and gets corrected rather than deleted.
     */
    public function destroy(AttendanceRecord $record): RedirectResponse
    {
        if ($record->clock_out_at !== null || $record->status === 'approved') {
            return back()->withErrors(['form' => __('This shift has been worked. Correct the times instead of deleting it.')]);
        }

        $name = $record->staff->full_name;
        $record->delete();

        return back()->with('status', __('Shift removed for :name.', ['name' => $name]));
    }

    /**
     * Somebody forgot to clock, and a manager is entering it after the fact.
     */
    public function storeManual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'staff_profile_id' => ['required', 'exists:staff_profiles,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'worked_on' => ['required', 'date', 'before_or_equal:today'],
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $staff = StaffProfile::findOrFail($data['staff_profile_id']);

        AttendanceRecord::create([
            'staff_profile_id' => $staff->id,
            'order_id' => $data['order_id'] ?? null,
            'worked_on' => $data['worked_on'],
            'clock_in_at' => $data['worked_on'].' '.$data['clock_in'],
            'clock_out_at' => $data['worked_on'].' '.$data['clock_out'],
            'break_minutes' => $data['break_minutes'] ?? 0,
            'method' => 'manual',
            'status' => 'closed',
            'hourly_rate' => $staff->hourly_rate,
            'overtime_rate' => $staff->overtime_rate,
            'recorded_by' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', __('Shift added for :name.', ['name' => $staff->full_name]));
    }
}
