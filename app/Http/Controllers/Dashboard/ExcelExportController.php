<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\InventoryItem;
use App\Models\StaffProfile;
use App\Services\CsvFile;
use App\Services\Timesheet;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sheets of the core records, for Excel.
 *
 * Staff and stock use exactly the columns the importer reads back, so the
 * client can export, change things in Excel and import the same file again.
 * Values are written in fixed English ("Full time", "Yes") rather than the
 * interface language so a file exported in Urdu still imports.
 */
class ExcelExportController extends Controller
{
    /** @var list<string> */
    public const STAFF_COLUMNS = [
        'Full name', 'Phone', 'Employment type', 'Department', 'Hourly rate',
        'Overtime rate', 'Holiday allowance hours', 'Start date', 'Active',
    ];

    /** @var list<string> */
    public const STOCK_COLUMNS = [
        'Name (English)', 'Name (Urdu)', 'Category', 'Unit', 'On hand', 'Reorder at',
        'Usual order', 'Cost per unit', 'Supplier', 'Code', 'Count how often', 'Active',
    ];

    /** @var list<string> */
    public const SHIFT_COLUMNS = [
        'Date', 'Name', 'Job', 'Rostered start', 'Clock in', 'Clock out',
        'Break minutes', 'Paid hours', 'Status',
    ];

    /** @var array<string, string> */
    public const EMPLOYMENT_TYPES = ['full_time' => 'Full time', 'part_time' => 'Part time', 'event_staff' => 'Event staff'];

    /** @var array<string, string> */
    public const DEPARTMENTS = ['kitchen' => 'Kitchen', 'service' => 'Service', 'delivery' => 'Delivery', 'management' => 'Management'];

    /** @var array<string, string> */
    public const COUNT_FREQUENCIES = ['weekly' => 'Weekly', 'daily' => 'Daily', 'per_event' => 'Before every job'];

    public function __construct(
        private readonly CsvFile $csv,
        private readonly Timesheet $timesheet,
    ) {}

    public function staff(): StreamedResponse
    {
        $rows = StaffProfile::orderBy('full_name')->get()
            ->map(fn (StaffProfile $s): array => [
                $s->full_name,
                $s->phone,
                self::EMPLOYMENT_TYPES[$s->employment_type] ?? $s->employment_type,
                self::DEPARTMENTS[$s->department] ?? $s->department,
                $s->hourly_rate,
                $s->overtime_rate,
                $s->holiday_allowance_hours,
                $s->started_on?->format('Y-m-d'),
                $s->is_active ? 'Yes' : 'No',
            ]);

        return $this->csv->download($this->filename('staff'), self::STAFF_COLUMNS, $rows);
    }

    public function stock(): StreamedResponse
    {
        $rows = InventoryItem::with(['category', 'supplier'])->orderBy('name_en')->get()
            ->map(fn (InventoryItem $i): array => [
                $i->name_en,
                $i->name_ur,
                $i->category?->name_en,
                $i->unit,
                qty($i->current_quantity),
                qty($i->reorder_level),
                $i->reorder_quantity === null ? '' : qty($i->reorder_quantity),
                $i->unit_cost,
                $i->supplier?->name,
                $i->sku,
                self::COUNT_FREQUENCIES[$i->count_frequency] ?? $i->count_frequency,
                $i->is_active ? 'Yes' : 'No',
            ]);

        return $this->csv->download($this->filename('stock'), self::STOCK_COLUMNS, $rows);
    }

    /**
     * Shifts for a date range, this week (Monday to Sunday) unless told otherwise.
     */
    public function shifts(Request $request): StreamedResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->filled('from')
            ? CarbonImmutable::parse($request->string('from')->toString())
            : $this->timesheet->weekStart();
        $to = $request->filled('to')
            ? CarbonImmutable::parse($request->string('to')->toString())
            : $from->addDays(6);

        $rows = AttendanceRecord::query()
            ->with([
                'staff' => fn ($q) => $q->withTrashed(),
                'order',
            ])
            ->whereBetween('worked_on', [$from->toDateString(), $to->toDateString()])
            ->orderBy('worked_on')
            ->orderBy('scheduled_start_at')
            ->orderBy('clock_in_at')
            ->get()
            ->map(fn (AttendanceRecord $r): array => [
                $r->worked_on->format('Y-m-d'),
                $r->staff?->full_name,
                $r->order ? trim($r->order->reference.' '.$r->order->customer_name) : 'Kitchen',
                $r->scheduled_start_at?->format('H:i'),
                $r->clock_in_at?->format('H:i'),
                $r->clock_out_at?->format('H:i'),
                $r->break_minutes,
                number_format($r->paidHours(), 2, '.', ''),
                $r->statusLabel(),
            ]);

        $filename = sprintf('midland-shifts-%s-to-%s.csv', $from->format('Y-m-d'), $to->format('Y-m-d'));

        return $this->csv->download($filename, self::SHIFT_COLUMNS, $rows);
    }

    private function filename(string $sheet): string
    {
        return sprintf('midland-%s-%s.csv', $sheet, now()->format('Y-m-d'));
    }
}
