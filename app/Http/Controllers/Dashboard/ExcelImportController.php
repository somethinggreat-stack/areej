<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\StaffProfile;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Activity;
use App\Services\CsvFile;
use App\Services\StockLedger;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bringing a sheet from Excel back in.
 *
 * Reads the same columns the export writes, so export → edit → import is a
 * round trip. Rows are matched by name and either added or updated; a row
 * that cannot be understood is skipped with its row number and the reason,
 * never half-saved. A column left out of the sheet is left alone on existing
 * records, so a two-column sheet of names and phone numbers is safe to import.
 */
class ExcelImportController extends Controller
{
    /**
     * Header spellings accepted for each stock column. The second spellings
     * are the Reports stock export, so that file imports too.
     *
     * @var array<string, list<string>>
     */
    private const STOCK_ALIASES = [
        'name_en' => ['name (english)', 'name english', 'name', 'item'],
        'name_ur' => ['name (urdu)', 'name urdu', 'item (urdu)'],
        'category' => ['category'],
        'unit' => ['unit'],
        'on_hand' => ['on hand', 'quantity'],
        'reorder_level' => ['reorder at', 'reorder level'],
        'reorder_quantity' => ['usual order', 'reorder quantity'],
        'unit_cost' => ['cost per unit', 'unit cost'],
        'supplier' => ['supplier'],
        'sku' => ['code', 'sku'],
        'count_frequency' => ['count how often'],
        'active' => ['active'],
    ];

    public function __construct(
        private readonly CsvFile $csv,
        private readonly Activity $activity,
        private readonly StockLedger $ledger,
    ) {}

    public function staffTemplate(): StreamedResponse
    {
        return $this->csv->download('midland-staff-template.csv', ExcelExportController::STAFF_COLUMNS, []);
    }

    public function stockTemplate(): StreamedResponse
    {
        return $this->csv->download('midland-stock-template.csv', ExcelExportController::STOCK_COLUMNS, []);
    }

    public function staff(Request $request): RedirectResponse
    {
        $sheet = $this->readUpload($request);

        if (! in_array('full name', $sheet['headers'], true)) {
            return $this->missingHeader('Full name');
        }

        $result = DB::transaction(fn (): array => $this->importRows($sheet['rows'], fn (array $row) => $this->importStaffRow($row)));

        $this->activity->log('imported', __('Staff imported from Excel: :summary', ['summary' => $this->summary($result)]), null, [
            'added' => $result['added'],
            'updated' => $result['updated'],
            'skipped' => count($result['skipped']),
        ]);

        return $this->finish('staff', $result);
    }

    public function stock(Request $request): RedirectResponse
    {
        $sheet = $this->readUpload($request);

        if ($this->cell(array_flip($sheet['headers']), self::STOCK_ALIASES['name_en']) === null) {
            return $this->missingHeader('Name (English)');
        }

        $result = DB::transaction(fn (): array => $this->importRows($sheet['rows'], fn (array $row) => $this->importStockRow($row, $request)));

        $this->activity->log('imported', __('Stock items imported from Excel: :summary', ['summary' => $this->summary($result)]), null, [
            'added' => $result['added'],
            'updated' => $result['updated'],
            'skipped' => count($result['skipped']),
        ]);

        return $this->finish('stock', $result);
    }

    /* ------------------------------------------------------------ staff */

    /**
     * @param  array<string, string>  $row
     * @return 'added'|'updated'
     */
    private function importStaffRow(array $row): string
    {
        $name = $this->cell($row, ['full name']);

        if ($name === null || $name === '') {
            throw new InvalidArgumentException(__('Full name is empty.'));
        }

        $staff = StaffProfile::whereRaw('lower(full_name) = ?', [mb_strtolower($name)])->first();

        $data = ['full_name' => $name];

        if (($phone = $this->cell($row, ['phone'])) !== null) {
            $data['phone'] = $phone === '' ? null : $this->phone($phone);
        }

        if (($type = $this->cell($row, ['employment type'])) !== null && $type !== '') {
            $data['employment_type'] = $this->choice($type, ExcelExportController::EMPLOYMENT_TYPES, __('Employment type'));
        }

        if (($department = $this->cell($row, ['department'])) !== null && $department !== '') {
            $data['department'] = $this->choice($department, ExcelExportController::DEPARTMENTS, __('Department'));
        }

        foreach (['hourly_rate' => 'hourly rate', 'overtime_rate' => 'overtime rate'] as $field => $column) {
            if (($rate = $this->cell($row, [$column])) !== null) {
                $data[$field] = $rate === '' ? null : $this->number($rate, ucfirst($column));
            }
        }

        if (($holiday = $this->cell($row, ['holiday allowance hours', 'holiday allowance'])) !== null && $holiday !== '') {
            $data['holiday_allowance_hours'] = $this->number($holiday, __('Holiday allowance hours'));
        }

        if (($started = $this->cell($row, ['start date', 'started on'])) !== null) {
            $data['started_on'] = $started === '' ? null : $this->date($started, __('Start date'));
        }

        if (($active = $this->cell($row, ['active'])) !== null && $active !== '') {
            $data['is_active'] = $this->yesNo($active, __('Active'));
        }

        $this->check($data, [
            'full_name' => ['string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'holiday_allowance_hours' => ['integer', 'min:0', 'max:2000'],
        ]);

        if ($staff === null) {
            StaffProfile::create($data + [
                'employment_type' => 'full_time',
                'department' => 'kitchen',
                'is_active' => true,
            ]);

            return 'added';
        }

        $staff->update($data);

        return 'updated';
    }

    /* ------------------------------------------------------------ stock */

    /**
     * @param  array<string, string>  $row
     * @return 'added'|'updated'
     */
    private function importStockRow(array $row, Request $request): string
    {
        $name = $this->stockCell($row, 'name_en');

        if ($name === null || $name === '') {
            throw new InvalidArgumentException(__('Name (English) is empty.'));
        }

        $item = InventoryItem::whereRaw('lower(name_en) = ?', [mb_strtolower($name)])->first();

        $data = ['name_en' => $name];

        if (($urdu = $this->stockCell($row, 'name_ur')) !== null) {
            $data['name_ur'] = $urdu === '' ? null : $urdu;
        }

        $category = $this->stockCell($row, 'category');

        if ($category !== null && $category !== '') {
            $match = InventoryCategory::whereRaw('lower(name_en) = ?', [mb_strtolower($category)])->first();

            if ($match === null) {
                throw new InvalidArgumentException(__('Category ":name" does not exist. Use one of: :list.', [
                    'name' => $category,
                    'list' => InventoryCategory::orderBy('name_en')->pluck('name_en')->implode(', '),
                ]));
            }

            $data['inventory_category_id'] = $match->id;
        } elseif ($item === null) {
            throw new InvalidArgumentException(__('Category is empty. A new item needs one.'));
        }

        if (($unit = $this->stockCell($row, 'unit')) !== null && $unit !== '') {
            $data['unit'] = $unit;
        }

        if (($reorderAt = $this->stockCell($row, 'reorder_level')) !== null && $reorderAt !== '') {
            $data['reorder_level'] = $this->number($reorderAt, __('Reorder at'));
        }

        if (($usual = $this->stockCell($row, 'reorder_quantity')) !== null) {
            $data['reorder_quantity'] = $usual === '' ? null : $this->number($usual, __('Usual order'));
        }

        if (($cost = $this->stockCell($row, 'unit_cost')) !== null) {
            $data['unit_cost'] = $cost === '' ? null : $this->number($cost, __('Cost per unit'));
        }

        if (($supplier = $this->stockCell($row, 'supplier')) !== null) {
            $data['supplier_id'] = null;

            if ($supplier !== '') {
                $data['supplier_id'] = Supplier::whereRaw('lower(name) = ?', [mb_strtolower($supplier)])->value('id')
                    ?? throw new InvalidArgumentException(__('Supplier ":name" does not exist. Add them under Suppliers first, or leave the cell empty.', ['name' => $supplier]));
            }
        }

        if (($code = $this->stockCell($row, 'sku')) !== null) {
            $data['sku'] = $code === '' ? null : $code;

            $taken = $code !== '' && InventoryItem::withTrashed()
                ->where('sku', $code)
                ->when($item, fn ($q) => $q->whereKeyNot($item->id))
                ->exists();

            if ($taken) {
                throw new InvalidArgumentException(__('Code ":code" is already used by another item.', ['code' => $code]));
            }
        }

        if (($frequency = $this->stockCell($row, 'count_frequency')) !== null && $frequency !== '') {
            $data['count_frequency'] = $this->choice($frequency, ExcelExportController::COUNT_FREQUENCIES, __('Count how often'));
        }

        if (($active = $this->stockCell($row, 'active')) !== null && $active !== '') {
            $data['is_active'] = $this->yesNo($active, __('Active'));
        }

        $this->check($data, [
            'name_en' => ['string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'unit' => ['string', 'max:24'],
            'sku' => ['nullable', 'string', 'max:64'],
            'reorder_level' => ['numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($item !== null) {
            // Quantity is never edited directly — a stock count corrects it.
            $item->update($data);

            return 'updated';
        }

        $onHand = $this->stockCell($row, 'on_hand');
        $opening = $onHand === null || $onHand === '' ? 0.0 : $this->number($onHand, __('On hand'));

        if ($opening < 0) {
            throw new InvalidArgumentException(__('On hand cannot be below zero.'));
        }

        $item = InventoryItem::create($data + [
            'unit' => 'kg',
            'reorder_level' => 0,
            'count_frequency' => 'weekly',
            'is_active' => true,
            'current_quantity' => 0,
        ]);

        // The opening figure goes through the ledger like everything else, so
        // the item's history starts where the stock actually started.
        if ($opening > 0) {
            $this->ledger->record(
                $item,
                StockMovement::TYPE_OPENING,
                $opening,
                $request->user(),
                null,
                $item->unit_cost,
                __('Opening balance'),
            );
        }

        return 'added';
    }

    /**
     * @param  array<string, string>  $row
     */
    private function stockCell(array $row, string $field): ?string
    {
        return $this->cell($row, self::STOCK_ALIASES[$field]);
    }

    /* ----------------------------------------------------------- shared */

    /**
     * @return array{headers: list<string>, rows: array<int, array<string, string>>}
     */
    private function readUpload(Request $request): array
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'file.mimes' => __('That is not a CSV file. In Excel use File → Save As and choose "CSV UTF-8".'),
        ]);

        return $this->csv->read($request->file('file'));
    }

    /**
     * Each row is saved or skipped on its own; one bad row never stops the rest.
     *
     * @param  array<int, array<string, string>>  $rows
     * @param  callable(array<string, string>): string  $importRow
     * @return array{added: int, updated: int, skipped: list<string>}
     */
    private function importRows(array $rows, callable $importRow): array
    {
        $result = ['added' => 0, 'updated' => 0, 'skipped' => []];

        foreach ($rows as $line => $row) {
            try {
                $result[$importRow($row)]++;
            } catch (InvalidArgumentException $e) {
                $result['skipped'][] = __('Row :n: :reason', ['n' => $line, 'reason' => $e->getMessage()]);
            }
        }

        return $result;
    }

    /**
     * @param  array{added: int, updated: int, skipped: list<string>}  $result
     */
    private function summary(array $result): string
    {
        return __(':added added, :updated updated, :skipped skipped', [
            'added' => $result['added'],
            'updated' => $result['updated'],
            'skipped' => count($result['skipped']),
        ]);
    }

    /**
     * @param  array{added: int, updated: int, skipped: list<string>}  $result
     */
    private function finish(string $sheet, array $result): RedirectResponse
    {
        return back()
            ->with('status', $this->summary($result))
            ->with('import_sheet', $sheet)
            ->with('import_skipped', $result['skipped']);
    }

    private function missingHeader(string $column): RedirectResponse
    {
        return back()->withErrors([
            'file' => __('The first row must be the column headings, including ":column". Download the blank template to see them.', ['column' => $column]),
        ]);
    }

    /**
     * First of the accepted header spellings present in the sheet. Null means
     * the column is not in the sheet at all; an empty string means it is but
     * this cell is blank.
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>  $names
     */
    private function cell(array $row, array $names): ?string
    {
        foreach ($names as $name) {
            if (array_key_exists($name, $row)) {
                return (string) $row[$name];
            }
        }

        return null;
    }

    /**
     * Accepts "£1,250.50" as well as 1250.5 — people type money the way they see it.
     */
    private function number(string $value, string $column): float
    {
        $clean = str_replace(['£', ',', ' '], '', $value);

        if (! is_numeric($clean)) {
            throw new InvalidArgumentException(__(':column ":value" is not a number.', ['column' => $column, 'value' => $value]));
        }

        return (float) $clean;
    }

    /**
     * Excel on a UK machine rewrites 2026-09-29 as 29/09/2026 when it saves, so
     * both are read, day first.
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
     * Matches either the label ("Event staff") or the stored value ("event_staff").
     *
     * @param  array<string, string>  $options
     */
    private function choice(string $value, array $options, string $column): string
    {
        $wanted = CsvFile::normalise(str_replace('_', ' ', $value));

        foreach ($options as $key => $label) {
            if ($wanted === CsvFile::normalise($label) || $wanted === str_replace('_', ' ', $key)) {
                return $key;
            }
        }

        throw new InvalidArgumentException(__(':column ":value" is not recognised. Use one of: :list.', [
            'column' => $column,
            'value' => $value,
            'list' => implode(', ', $options),
        ]));
    }

    private function yesNo(string $value, string $column): bool
    {
        return match (mb_strtolower($value)) {
            'yes', 'y', 'true', '1' => true,
            'no', 'n', 'false', '0' => false,
            default => throw new InvalidArgumentException(__(':column ":value" should be Yes or No.', ['column' => $column, 'value' => $value])),
        };
    }

    /**
     * Excel drops the leading zero from a phone number typed as digits, so a
     * ten-digit UK mobile starting with 7 gets it back.
     */
    private function phone(string $value): string
    {
        return preg_match('/^7\d{9}$/', $value) === 1 ? '0'.$value : $value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, list<string>>  $rules
     */
    private function check(array $data, array $rules): void
    {
        $validator = Validator::make($data, array_intersect_key($rules, $data));

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }
    }
}
