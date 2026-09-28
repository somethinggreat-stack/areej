<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Services\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Monday counts, and the shorter ones in between.
 *
 * A count is a draft while it is being walked round the store — phones lose
 * signal in there, and Areej and Kabir split the list — so lines save one at a
 * time and nothing touches stock until the sheet is submitted.
 */
class StockCountController extends Controller
{
    public function __construct(private readonly StockLedger $ledger) {}

    public function index(): View
    {
        return view('dashboard.stock-counts.index', [
            'counts' => StockCount::with(['openedBy', 'completedBy'])
                ->withCount('lines')
                ->latest('counted_on')
                ->paginate(20),
            'openDraft' => StockCount::draft()->latest()->first(),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.stock-counts.create', [
            'weeklyCount' => InventoryItem::countedWeekly()->count(),
            'dailyCount' => InventoryItem::where('is_active', true)->where('count_frequency', 'daily')->count(),
            'perEventCount' => InventoryItem::where('is_active', true)->where('count_frequency', 'per_event')->count(),
            'openDraft' => StockCount::draft()->latest()->first(),
        ]);
    }

    /**
     * Builds the sheet. A weekly count covers everything; the shorter scopes
     * cover only the lines flagged to be watched that often.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'in:weekly,daily,per_event'],
            'counted_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $items = InventoryItem::where('is_active', true)
            ->when($data['scope'] !== 'weekly', fn ($q) => $q->where('count_frequency', $data['scope']))
            ->orderBy('inventory_category_id')
            ->orderBy('name_en')
            ->get();

        if ($items->isEmpty()) {
            return back()->withErrors(['scope' => __('There are no items flagged for that kind of count.')]);
        }

        $count = DB::transaction(function () use ($data, $items, $request) {
            $count = StockCount::create([
                'counted_on' => $data['counted_on'],
                'scope' => $data['scope'],
                'status' => 'draft',
                'opened_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Expected quantities are frozen now so the variance still means
            // something if stock moves while the count is being walked.
            $count->lines()->createMany(
                $items->map(fn (InventoryItem $item) => [
                    'inventory_item_id' => $item->id,
                    'expected_quantity' => $item->current_quantity,
                ])->all()
            );

            return $count;
        });

        return redirect()->route('stock-counts.show', $count);
    }

    public function show(StockCount $stockCount): View
    {
        return view('dashboard.stock-counts.show', [
            'count' => $stockCount->load(['openedBy', 'completedBy']),
            'lines' => $stockCount->lines()
                ->with(['item.category', 'item.supplier'])
                ->get()
                ->sortBy(fn (StockCountLine $line) => [$line->item->category?->displayName() ?? '', $line->item->name_en])
                ->groupBy(fn (StockCountLine $line) => $line->item->category?->displayName() ?? __('Uncategorised')),
        ]);
    }

    /**
     * Only an unsubmitted sheet can be thrown away — a completed count has
     * already moved stock and is part of the ledger.
     */
    public function destroy(StockCount $stockCount): RedirectResponse
    {
        if (! $stockCount->isDraft()) {
            return back()->withErrors(['form' => __('A submitted count cannot be deleted — it has already corrected stock.')]);
        }

        $reference = $stockCount->reference;
        $stockCount->delete();

        return redirect()->route('stock-counts')->with('status', __('Count :ref discarded.', ['ref' => $reference]));
    }

    /**
     * Saves one line. Called per field as the count is walked, so a dropped
     * connection costs one number rather than the whole sheet.
     */
    public function update(Request $request, StockCount $stockCount): RedirectResponse
    {
        abort_unless($stockCount->isDraft(), 422, __('This count has already been submitted.'));

        $data = $request->validate([
            'line_id' => ['required', 'exists:stock_count_lines,id'],
            'counted_quantity' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $line = $stockCount->lines()->findOrFail($data['line_id']);

        $line->update([
            'counted_quantity' => $data['counted_quantity'],
            'notes' => $data['notes'] ?? $line->notes,
        ]);

        return back()->with('status', __('Saved.'));
    }

    /**
     * Submits the sheet: every counted line that disagrees with the system
     * writes a ledger adjustment. Uncounted lines are left alone rather than
     * being treated as zero.
     */
    public function complete(Request $request, StockCount $stockCount): RedirectResponse
    {
        abort_unless($stockCount->isDraft(), 422, __('This count has already been submitted.'));

        $counted = $stockCount->lines()->whereNotNull('counted_quantity')->with('item')->get();

        if ($counted->isEmpty()) {
            return back()->withErrors(['count' => __('Nothing has been counted yet.')]);
        }

        $adjusted = 0;

        DB::transaction(function () use ($counted, $stockCount, $request, &$adjusted): void {
            foreach ($counted as $line) {
                $movement = $this->ledger->setTo(
                    $line->item,
                    (float) $line->counted_quantity,
                    $request->user(),
                    $stockCount,
                    __('Count :ref', ['ref' => $stockCount->reference]),
                );

                if ($movement !== null) {
                    $adjusted++;
                }
            }

            $stockCount->update([
                'status' => 'completed',
                'completed_by' => $request->user()->id,
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('stock-counts.show', $stockCount)->with('status', trans_choice(
            '{0}Count submitted. Everything matched.|{1}Count submitted. 1 item was corrected.|[2,*]Count submitted. :count items were corrected.',
            $adjusted,
            ['count' => $adjusted],
        ));
    }
}
