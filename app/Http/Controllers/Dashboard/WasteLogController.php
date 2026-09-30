<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\WasteLog;
use App\Services\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Logging waste takes stock off the shelf as well as recording the reason, so
 * it always goes through the ledger.
 */
class WasteLogController extends Controller
{
    public function __construct(private readonly StockLedger $ledger) {}

    public function index(Request $request): View
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();
        $type = array_key_exists($request->string('type')->toString(), WasteLog::typeLabels())
            ? $request->string('type')->toString()
            : '';

        $logs = WasteLog::with(['item', 'logger', 'order'])
            ->whereDate('wasted_on', '>=', $from)
            ->whereDate('wasted_on', '<=', $to)
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest('wasted_on')
            ->paginate(40)
            ->withQueryString();

        $inRange = WasteLog::whereDate('wasted_on', '>=', $from)->whereDate('wasted_on', '<=', $to)->get();
        $summarise = fn ($group) => [
            'count' => $group->count(),
            'cost' => $group->sum(fn (WasteLog $log) => $log->cost()),
        ];

        return view('dashboard.waste.index', [
            'logs' => $logs,
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'totalCost' => $inRange->sum(fn (WasteLog $log) => $log->cost()),
            'byType' => $inRange->groupBy('type')->map($summarise)->sortByDesc('count'),
            'byReason' => $inRange->groupBy('reason')->map($summarise)->sortByDesc('cost'),
            'items' => InventoryItem::where('is_active', true)->orderBy('name_en')->get(),
            'orders' => Order::upcoming()->limit(30)->get(),
        ]);
    }

    /**
     * Deleting a waste entry puts the stock back, because the entry taking it
     * off the shelf was the mistake being corrected.
     */
    public function destroy(WasteLog $wasteLog): RedirectResponse
    {
        DB::transaction(function () use ($wasteLog): void {
            // Waste that was not a stock item never took anything off the shelf.
            if ($wasteLog->item !== null) {
                $this->ledger->record(
                    $wasteLog->item,
                    StockMovement::TYPE_MANUAL,
                    (float) $wasteLog->quantity,
                    request()->user(),
                    null,
                    $wasteLog->unit_cost,
                    __('Waste entry reversed'),
                );
            }

            $wasteLog->delete();
        });

        return back()->with('status', $wasteLog->inventory_item_id
            ? __('Waste entry reversed and the stock put back.')
            : __('Waste entry removed.'));
    }

    /**
     * A stock item comes off the shelf through the ledger. Anything else —
     * leftover cooked food, mostly — is written in by name and only recorded.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:'.implode(',', array_keys(WasteLog::typeLabels()))],
            'inventory_item_id' => ['nullable', 'required_without:description', 'exists:inventory_items,id'],
            'description' => ['nullable', 'required_without:inventory_item_id', 'string', 'max:255'],
            'unit' => ['nullable', 'in:'.implode(',', array_keys(catering_units(true)))],
            'order_id' => ['nullable', 'exists:orders,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'in:spoilage,over_production,returned,damaged,other'],
            'wasted_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'inventory_item_id.required_without' => __('Choose the stock item, or write what it was.'),
            'description.required_without' => __('Choose the stock item, or write what it was.'),
        ]);

        $item = filled($data['inventory_item_id'] ?? null) ? InventoryItem::with('category')->findOrFail($data['inventory_item_id']) : null;

        if ($item !== null && (float) $item->current_quantity < (float) $data['quantity']) {
            return back()->withErrors([
                'quantity' => __('There is only :qty :unit of :name on hand.', [
                    'qty' => qty($item->current_quantity),
                    'unit' => unit_label($item->unit),
                    'name' => $item->displayName(),
                ]),
            ])->withInput();
        }

        DB::transaction(function () use ($data, $item, $request): void {
            $log = WasteLog::create([
                ...$data,
                'type' => ($data['type'] ?? null) ?: ($item ? WasteLog::typeFor($item) : 'cooked_food'),
                'description' => $item ? null : $data['description'],
                'unit' => $item ? null : ($data['unit'] ?? null),
                // Frozen so the cost of waste stays true after a repricing.
                'unit_cost' => $item?->unit_cost,
                'logged_by' => $request->user()->id,
            ]);

            if ($item !== null) {
                $this->ledger->record(
                    $item,
                    StockMovement::TYPE_WASTE,
                    -1 * (float) $data['quantity'],
                    $request->user(),
                    $log,
                    $item->unit_cost,
                    $log->reasonLabel(),
                );
            }
        });

        return back()->with('status', __('Waste recorded.'));
    }
}
