<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Setting;
use App\Models\StaffProfile;
use App\Models\WagePayment;
use App\Models\WasteLog;
use App\Services\Activity;
use App\Services\CsvFile;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * The offline copy: everything that matters in one zip of Excel sheets.
 *
 * The client's worry is losing money records if the website ever fails, so the
 * backup is plain CSV files anyone can open, and the same zip can be uploaded
 * again to put back any orders, payments and wages that are missing. Restoring
 * only ever adds what is not already there; it never overwrites or deletes.
 */
class BackupController extends Controller
{
    /** @var list<string> */
    public const ORDER_COLUMNS = [
        'Reference', 'Date', 'Customer', 'Phone', 'Email', 'Address', 'Status',
        'Order amount', 'Paid', 'Still owed', 'Payment status', 'Notes',
    ];

    /** @var list<string> */
    public const ITEM_COLUMNS = ['Reference', 'Item', 'Quantity', 'Unit', 'Price', 'Line total'];

    /** @var list<string> */
    public const PAYMENT_COLUMNS = ['Reference', 'Customer', 'Paid on', 'Amount', 'Paid by', 'Type', 'Payment reference', 'Notes'];

    /** @var list<string> */
    public const CUSTOMER_COLUMNS = ['Customer', 'Phone', 'Orders', 'Total', 'Paid', 'Still owed', 'Last order'];

    /** @var list<string> */
    public const WASTE_COLUMNS = ['Date', 'Type', 'Item', 'Quantity', 'Unit', 'Reason', 'Order', 'Notes'];

    public function __construct(
        private readonly CsvFile $csv,
        private readonly ExcelExportController $sheets,
        private readonly Activity $activity,
    ) {}

    public function index(): View
    {
        $last = Setting::get('last_backup_at');

        return view('dashboard.backup.index', [
            'lastBackup' => $last ? Carbon::parse($last) : null,
            'counts' => [
                'orders' => Order::count(),
                'payments' => OrderPayment::count(),
                'wages' => WagePayment::count(),
            ],
        ]);
    }

    public function download(Request $request): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'midland-backup');

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($this->files() as $name => [$headers, $rows]) {
            $zip->addFromString($name, $this->csv->toString($headers, $rows));
        }

        $zip->addFromString('READ ME.txt', $this->readMe());
        $zip->close();

        Setting::put('last_backup_at', now()->toDateTimeString(), 'string', 'backup');

        $this->activity->log('exported', __('Full backup downloaded'));

        return response()
            ->download($path, sprintf('midland-backup-%s.zip', now()->format('Y-m-d-Hi')), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    /**
     * Put back from a backup zip whatever is missing: orders (with their items),
     * payments and wages. Anything already on the website is left as it is.
     */
    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:zip', 'max:20480'],
        ], [
            'file.mimes' => __('That is not a backup file. Upload the .zip saved from "Download everything".'),
        ]);

        $sheets = $this->readZip($request->file('file')->getRealPath());

        if (! isset($sheets['orders.csv'])) {
            return back()->withErrors(['file' => __('This zip has no orders.csv in it. Upload the .zip saved from "Download everything".')]);
        }

        $result = DB::transaction(fn (): array => $this->restoreSheets($sheets, $request));

        $summary = __(':orders orders, :payments payments and :wages wages put back. :already orders were already on the website.', [
            'orders' => $result['orders'],
            'payments' => $result['payments'],
            'wages' => $result['wages'],
            'already' => $result['already'],
        ]);

        $this->activity->log('imported', __('Backup restored: :summary', ['summary' => $summary]), null, [
            'orders' => $result['orders'],
            'payments' => $result['payments'],
            'wages' => $result['wages'],
            'skipped' => count($result['skipped']),
        ]);

        return back()
            ->with('status', $summary)
            ->with('restore_skipped', $result['skipped']);
    }

    /**
     * @return array<string, array{0: list<string>, 1: iterable<int, array<int, mixed>>}>
     */
    private function files(): array
    {
        $orders = Order::with(['items', 'payments'])->orderBy('event_date')->orderBy('id')->get();
        $pounds = fn (int $pence): string => number_format($pence / 100, 2, '.', '');

        return [
            'orders.csv' => [self::ORDER_COLUMNS, $orders->map(fn (Order $o): array => [
                $o->reference,
                $o->event_date?->format('Y-m-d'),
                $o->customer_name,
                $o->phone,
                $o->email,
                $o->venue_address,
                $o->statusLabel(),
                $pounds((int) $o->total_amount),
                $pounds($o->paidAmount()),
                $pounds($o->balanceAmount()),
                $o->paymentStatusLabel(),
                $o->notes,
            ])],
            'order-items.csv' => [self::ITEM_COLUMNS, $orders->flatMap(fn (Order $o) => $o->items->map(fn ($item): array => [
                $o->reference,
                $item->description,
                qty($item->quantity),
                unit_label($item->unit),
                $pounds((int) $item->unit_price),
                $pounds((int) $item->line_total),
            ]))],
            'payments.csv' => [self::PAYMENT_COLUMNS, $orders->flatMap(fn (Order $o) => $o->payments->map(fn (OrderPayment $p): array => [
                $o->reference,
                $o->customer_name,
                $p->paid_on->format('Y-m-d'),
                $pounds($p->amount),
                $p->methodLabel(),
                $p->kindLabel(),
                $p->reference,
                $p->notes,
            ]))],
            'customers.csv' => [self::CUSTOMER_COLUMNS, CustomerController::customers()->map(fn (array $c): array => [
                $c['name'],
                $c['phone'],
                $c['orders'],
                $pounds($c['total']),
                $pounds($c['paid']),
                $pounds($c['owed']),
                $c['last']?->format('Y-m-d'),
            ])],
            'staff.csv' => [ExcelExportController::STAFF_COLUMNS, $this->sheets->staffRows()],
            'shifts.csv' => [ExcelExportController::SHIFT_COLUMNS, $this->sheets->shiftRows()],
            'wages.csv' => [ExcelExportController::WAGE_COLUMNS, $this->sheets->wageRows()],
            'stock.csv' => [ExcelExportController::STOCK_COLUMNS, $this->sheets->stockRows()],
            'waste.csv' => [self::WASTE_COLUMNS, WasteLog::with(['item', 'order'])->orderBy('wasted_on')->get()->map(fn (WasteLog $w): array => [
                $w->wasted_on->format('Y-m-d'),
                $w->typeLabel(),
                $w->item?->name_en ?? $w->description,
                qty($w->quantity),
                $w->unitLabel(),
                $w->reasonLabel(),
                $w->order?->reference,
                $w->notes,
            ])],
        ];
    }

    private function readMe(): string
    {
        return implode("\r\n", [
            'Midland Catering - full backup, '.now()->format('j F Y, H:i'),
            '',
            'Each file opens in Excel:',
            '  orders.csv       one row per order, with what was paid and what is still owed',
            '  order-items.csv  what was on each order (matched by Reference)',
            '  payments.csv     every payment taken, by order',
            '  customers.csv    each customer and their balance',
            '  staff.csv, shifts.csv, wages.csv, stock.csv, waste.csv',
            '',
            'To put records back: Dashboard > Backup > Restore from a backup, and upload this zip as it is.',
            'Orders, payments and wages that are missing are added. Nothing already there is changed.',
            'Staff, stock and shifts go back through the Import from Excel box on their own pages.',
        ])."\r\n";
    }

    /**
     * Sheets in the zip keyed by lower-case file name, so a zip re-made by hand
     * with the files in a folder still reads.
     *
     * @return array<string, array{headers: list<string>, rows: array<int, array<string, string>>}>
     */
    private function readZip(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return [];
        }

        $sheets = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = mb_strtolower(basename((string) $zip->getNameIndex($i)));

            if (str_ends_with($name, '.csv')) {
                $sheets[$name] = $this->csv->readString((string) $zip->getFromIndex($i));
            }
        }

        $zip->close();

        return $sheets;
    }

    /**
     * @param  array<string, array{headers: list<string>, rows: array<int, array<string, string>>}>  $sheets
     * @return array{orders: int, payments: int, wages: int, already: int, skipped: list<string>}
     */
    private function restoreSheets(array $sheets, Request $request): array
    {
        $result = ['orders' => 0, 'payments' => 0, 'wages' => 0, 'already' => 0, 'skipped' => []];

        $orders = Order::withTrashed()->get()->keyBy('reference');
        $added = [];

        foreach ($sheets['orders.csv']['rows'] as $line => $row) {
            $this->attempt($result, 'orders.csv', $line, function () use ($row, $orders, &$added, &$result, $request): void {
                $reference = $this->required($row, 'reference', 'Reference');

                if ($orders->has($reference)) {
                    $result['already']++;

                    return;
                }

                $order = Order::create([
                    'reference' => $reference,
                    'event_date' => $this->date($this->required($row, 'date', 'Date'), 'Date'),
                    'customer_name' => $this->required($row, 'customer', 'Customer'),
                    'phone' => ($row['phone'] ?? '') ?: null,
                    'email' => ($row['email'] ?? '') ?: null,
                    'venue_address' => ($row['address'] ?? '') ?: null,
                    'status' => $this->choice($row['status'] ?? '', Order::statusLabels(), 'confirmed'),
                    'total_amount' => $this->pence($row['order amount'] ?? '', 'Order amount'),
                    'notes' => ($row['notes'] ?? '') ?: null,
                    'service_style' => 'not_set',
                    'source' => 'backup',
                    'taken_by' => $request->user()->id,
                ]);

                $orders->put($reference, $order);
                $added[$reference] = true;
                $result['orders']++;
            });
        }

        foreach ($sheets['order-items.csv']['rows'] ?? [] as $line => $row) {
            $this->attempt($result, 'order-items.csv', $line, function () use ($row, $orders, $added): void {
                $reference = $this->required($row, 'reference', 'Reference');

                // Items only go onto orders this restore added; an order already
                // on the website keeps the items it has.
                if (! isset($added[$reference])) {
                    return;
                }

                $order = $orders->get($reference);
                $price = $this->pence($row['price'] ?? '', 'Price');
                $quantity = ($row['quantity'] ?? '') === '' ? 1.0 : $this->number($row['quantity'], 'Quantity');

                $order->items()->create([
                    'description' => $this->required($row, 'item', 'Item'),
                    'quantity' => $quantity,
                    'unit' => $this->unit($row['unit'] ?? ''),
                    'unit_price' => $price,
                    'line_total' => ($row['line total'] ?? '') === '' ? (int) round($quantity * $price) : $this->pence($row['line total'], 'Line total'),
                    'position' => (int) $order->items()->max('position') + 1,
                ]);
            });
        }

        foreach ($sheets['payments.csv']['rows'] ?? [] as $line => $row) {
            $this->attempt($result, 'payments.csv', $line, function () use ($row, $orders, &$result, $request): void {
                $reference = $this->required($row, 'reference', 'Reference');
                $order = $orders->get($reference)
                    ?? throw new InvalidArgumentException(__('Order :ref is not on the website or in orders.csv.', ['ref' => $reference]));

                $payment = [
                    'paid_on' => $this->date($this->required($row, 'paid on', 'Paid on'), 'Paid on'),
                    'amount' => $this->pence($this->required($row, 'amount', 'Amount'), 'Amount'),
                    'method' => $this->choice($row['paid by'] ?? '', OrderPayment::methodLabels(), 'cash'),
                ];

                // The same payment on the same order is already there.
                if ($order->payments()->whereDate('paid_on', $payment['paid_on'])
                    ->where('amount', $payment['amount'])->where('method', $payment['method'])->exists()) {
                    return;
                }

                $order->payments()->create($payment + [
                    'kind' => $this->choice($row['type'] ?? '', OrderPayment::kindLabels(), 'part_payment'),
                    'reference' => ($row['payment reference'] ?? '') ?: null,
                    'notes' => ($row['notes'] ?? '') ?: null,
                    'recorded_by' => $request->user()->id,
                ]);

                $result['payments']++;
            });
        }

        $staff = StaffProfile::withTrashed()->get()->keyBy(fn (StaffProfile $s) => CsvFile::normalise($s->full_name));

        foreach ($sheets['wages.csv']['rows'] ?? [] as $line => $row) {
            $this->attempt($result, 'wages.csv', $line, function () use ($row, $staff, &$result, $request): void {
                $name = $this->required($row, 'name', 'Name');
                $person = $staff->get(CsvFile::normalise($name))
                    ?? throw new InvalidArgumentException(__('":name" is not on the Staff page. Import staff.csv first.', ['name' => $name]));

                $wage = [
                    'staff_profile_id' => $person->id,
                    'week_start' => $this->date($this->required($row, 'week starting', 'Week starting'), 'Week starting'),
                    'amount' => $this->pence($this->required($row, 'amount', 'Amount'), 'Amount'),
                ];

                if (WagePayment::where('staff_profile_id', $wage['staff_profile_id'])
                    ->whereDate('week_start', $wage['week_start'])->where('amount', $wage['amount'])->exists()) {
                    return;
                }

                WagePayment::create($wage + [
                    'method' => $this->choice($row['paid by'] ?? '', WagePayment::methodLabels(), 'cash'),
                    'paid_on' => ($row['paid on'] ?? '') === '' ? $wage['week_start'] : $this->date($row['paid on'], 'Paid on'),
                    'notes' => ($row['notes'] ?? '') ?: null,
                    'recorded_by' => $request->user()->id,
                ]);

                $result['wages']++;
            });
        }

        return $result;
    }

    /**
     * One row that cannot be read is listed and skipped; the rest still go in.
     *
     * @param  array{orders: int, payments: int, wages: int, already: int, skipped: list<string>}  $result
     */
    private function attempt(array &$result, string $file, int $line, callable $restoreRow): void
    {
        try {
            $restoreRow();
        } catch (InvalidArgumentException $e) {
            $result['skipped'][] = __(':file row :n: :reason', ['file' => $file, 'n' => $line, 'reason' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, string>  $row
     */
    private function required(array $row, string $header, string $column): string
    {
        $value = trim((string) ($row[$header] ?? ''));

        if ($value === '') {
            throw new InvalidArgumentException(__(':column is empty.', ['column' => $column]));
        }

        return $value;
    }

    private function number(string $value, string $column): float
    {
        $clean = str_replace(['£', ',', ' '], '', $value);

        if (! is_numeric($clean)) {
            throw new InvalidArgumentException(__(':column ":value" is not a number.', ['column' => $column, 'value' => $value]));
        }

        return (float) $clean;
    }

    private function pence(string $value, string $column): int
    {
        return $value === '' ? 0 : (int) round($this->number($value, $column) * 100);
    }

    /**
     * Excel on a UK machine rewrites 2026-09-29 as 29/09/2026 when it saves.
     */
    private function date(string $value, string $column): string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd/m/y', 'd-m-Y', 'd.m.Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);

            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        throw new InvalidArgumentException(__(':column ":value" is not a date. Write it like 29/09/2026.', ['column' => $column, 'value' => $value]));
    }

    /**
     * The label ("Bank transfer") or the stored value ("bank_transfer"); a blank
     * or unknown cell falls back rather than losing the row.
     *
     * @param  array<string, string>  $options
     */
    private function choice(string $value, array $options, string $fallback): string
    {
        $wanted = CsvFile::normalise(str_replace('_', ' ', $value));

        foreach ($options as $key => $label) {
            if ($wanted === CsvFile::normalise($label) || $wanted === str_replace('_', ' ', $key)) {
                return $key;
            }
        }

        return $fallback;
    }

    private function unit(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        foreach (catering_units(true) as $key => $label) {
            if (in_array(CsvFile::normalise($value), [$key, CsvFile::normalise($label)], true)) {
                return $key;
            }
        }

        return $value;
    }
}
