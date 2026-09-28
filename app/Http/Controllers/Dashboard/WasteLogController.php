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

        $logs = WasteLog::with(['item', 'logger', 'order'])
            ->whereBetween('wasted_on', [$from, $to])
            ->latest('wasted_on')
            ->paginate(40)
            ->withQueryString();

        $inRange = WasteLog::whereBetween('wasted_on', [$from, $to])->get();

        return view('dashboard.waste.index', [
            'logs' => $logs,
            'from' => $from,
            'to' => $to,
            'totalCost' => $inRange->sum(fn (WasteLog $log) => $log->cost()),
            'byReason' => $inRange->groupBy('reason')
                ->map(fn ($group) => [
                    'count' => $group->count(),
                    'cost' => $group->sum(fn (WasteLog $log) => $log->cost()),
                ])
                ->sortByDesc('cost'),
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

        return back()->with('status', __('Waste entry reversed and the stock put back.'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'in:spoilage,over_production,returned,damaged,other'],
            'wasted_on' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = InventoryItem::findOrFail($data['inventory_item_id']);

        if ((float) $item->current_quantity < (float) $data['quantity']) {
            return back()->withErrors([
                'quantity' => __('There is only :qty :unit of :name on hand.', [
                    'qty' => qty($item->current_quantity),
                    'unit' => $item->unit,
                    'name' => $item->displayName(),
                ]),
            ])->withInput();
        }

        DB::transaction(function () use ($data, $item, $request): void {
            $log = WasteLog::create([
                ...$data,
                // Frozen so the cost of waste stays true after a repricing.
                'unit_cost' => $item->unit_cost,
                'logged_by' => $request->user()->id,
            ]);

            $this->ledger->record(
                $item,
                StockMovement::TYPE_WASTE,
                -1 * (float) $data['quantity'],
                $request->user(),
                $log,
                $item->unit_cost,
                $log->reasonLabel(),
            );
        });

        return back()->with('status', __('Waste recorded.'));
    }
}
