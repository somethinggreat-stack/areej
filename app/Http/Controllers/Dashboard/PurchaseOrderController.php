<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Ordering from suppliers.
 *
 * The low-stock list is grouped by supplier so one order covers everything that
 * comes from them, which is how the client actually orders. Nothing moves stock
 * until a delivery is booked in.
 */
class PurchaseOrderController extends Controller
{
    public function __construct(private readonly StockLedger $ledger) {}

    public function index(): View
    {
        $lowStock = InventoryItem::needsReorder()
            ->with('supplier')
            ->orderBy('name_en')
            ->get()
            ->groupBy(fn (InventoryItem $item) => $item->supplier?->name ?? __('No supplier set'));

        return view('dashboard.purchase-orders.index', [
            'orders' => PurchaseOrder::with(['supplier', 'lines'])->latest()->paginate(20),
            'outstanding' => PurchaseOrder::outstanding()->with('supplier')->orderBy('expected_on')->get(),
            'lowStock' => $lowStock,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'expected_on' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity_ordered' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            $order = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'status' => 'draft',
                'ordered_on' => today(),
                'expected_on' => $data['expected_on'] ?? null,
                'created_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                $item = InventoryItem::find($line['inventory_item_id']);

                $order->lines()->create([
                    'inventory_item_id' => $line['inventory_item_id'],
                    'quantity_ordered' => $line['quantity_ordered'],
                    'unit_cost' => $line['unit_cost'] ?? $item?->unit_cost,
                ]);
            }

            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)
            ->with('status', __('Order :ref created.', ['ref' => $order->reference]));
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        return view('dashboard.purchase-orders.show', [
            'order' => $purchaseOrder->load(['supplier', 'lines.item', 'creator']),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,sent,cancelled'],
            'expected_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $purchaseOrder->update($data);

        return back()->with('status', __('Order updated.'));
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        // Once anything has been received, the order is part of the stock trail.
        if ($purchaseOrder->lines()->where('quantity_received', '>', 0)->exists()) {
            return back()->withErrors(['form' => __('Part of this delivery has already been booked in, so it cannot be deleted. Cancel it instead.')]);
        }

        $reference = $purchaseOrder->reference;
        $purchaseOrder->delete();

        return redirect()->route('purchase-orders')
            ->with('status', __('Order :ref deleted.', ['ref' => $reference]));
    }

    /**
     * Book in a delivery. Suppliers short-deliver constantly, so each line is
     * received individually and the order only closes when everything has
     * arrived.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $data = $request->validate([
            'received_on' => ['required', 'date', 'before_or_equal:today'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'exists:purchase_order_lines,id'],
            'lines.*.quantity_received' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $purchaseOrder, $request): void {
            foreach ($data['lines'] as $input) {
                $line = $purchaseOrder->lines()->with('item')->findOrFail($input['id']);

                $receivedNow = (float) $input['quantity_received'];

                if ($receivedNow <= 0) {
                    continue;
                }

                $unitCost = $input['unit_cost'] ?? $line->unit_cost;

                $this->ledger->record(
                    $line->item,
                    StockMovement::TYPE_PURCHASE,
                    $receivedNow,
                    $request->user(),
                    $purchaseOrder,
                    $unitCost,
                    __('Delivery :ref', ['ref' => $purchaseOrder->reference]),
                );

                $line->update([
                    'quantity_received' => (float) $line->quantity_received + $receivedNow,
                    'unit_cost' => $unitCost,
                ]);

                // A delivery is the freshest price we have for this item.
                if ($unitCost !== null) {
                    $line->item->update(['unit_cost' => $unitCost]);
                }
            }

            $purchaseOrder->refresh()->load('lines');

            $allIn = $purchaseOrder->lines->every(fn ($line) => $line->outstanding() <= 0);

            $purchaseOrder->update([
                'status' => $allIn ? 'received' : 'part_received',
                'received_on' => $data['received_on'],
                'received_by' => $request->user()->id,
            ]);
        });

        return back()->with('status', __('Delivery booked in.'));
    }
}
