<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Activity;
use App\Services\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function __construct(private readonly StockLedger $ledger) {}

    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();

        $items = InventoryItem::query()
            ->with(['category', 'supplier'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($q) => $q->where('name_en', 'like', $term)
                    ->orWhere('name_ur', 'like', $term)
                    ->orWhere('sku', 'like', $term));
            })
            ->when($request->filled('category'), fn ($q) => $q->where('inventory_category_id', $request->integer('category')))
            ->when($filter === 'low', fn ($q) => $q->needsReorder())
            ->when($filter !== 'archived', fn ($q) => $q->where('is_active', true))
            ->when($filter === 'archived', fn ($q) => $q->where('is_active', false))
            ->orderBy('name_en')
            ->paginate(30)
            ->withQueryString();

        return view('dashboard.inventory.index', [
            'items' => $items,
            'categories' => InventoryCategory::orderBy('name_en')->get(),
            'lowStockCount' => InventoryItem::needsReorder()->count(),
            'filter' => $filter,
        ]);
    }

    public function show(InventoryItem $item): View
    {
        return view('dashboard.inventory.show', [
            'item' => $item->load(['category', 'supplier']),
            'movements' => $item->movements()->with('recorder')->limit(50)->get(),
            'wasteThisMonth' => $item->wasteLogs()
                ->whereBetween('wasted_on', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('quantity'),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.inventory.form', [
            'item' => new InventoryItem(['unit' => 'kg', 'count_frequency' => 'weekly', 'is_active' => true]),
            'categories' => InventoryCategory::orderBy('name_en')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(InventoryItem $item): View
    {
        return view('dashboard.inventory.form', [
            'item' => $item,
            'categories' => InventoryCategory::orderBy('name_en')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // The opening figure goes through the ledger like everything else, so
        // the item's history starts where the stock actually started.
        $opening = (float) ($data['current_quantity'] ?? 0);
        $data['current_quantity'] = 0;

        $item = InventoryItem::create($data);

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

        return redirect()->route('inventory.show', $item)
            ->with('status', __(':name added.', ['name' => $item->name_en]));
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $data = $this->validated($request, $item);

        // Quantity is never edited directly — that is what adjustments are for.
        unset($data['current_quantity']);

        $item->update($data);

        return redirect()->route('inventory.show', $item)
            ->with('status', __(':name updated.', ['name' => $item->name_en]));
    }

    /**
     * A manual correction outside of a count — a bag split, a miscount spotted
     * later. Recorded as its own movement type so it never hides inside the
     * Monday count figures.
     */
    public function adjust(Request $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validate([
            'quantity_change' => ['required', 'numeric', 'not_in:0'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $change = (float) $data['quantity_change'];

        if ((float) $item->current_quantity + $change < 0) {
            return back()->withErrors([
                'quantity_change' => __('That would take stock below zero. There is :qty :unit on hand.', [
                    'qty' => qty($item->current_quantity),
                    'unit' => $item->unit,
                ]),
            ])->withInput();
        }

        $this->ledger->record(
            $item,
            StockMovement::TYPE_MANUAL,
            $change,
            $request->user(),
            null,
            null,
            $data['notes'],
        );

        return back()->with('status', __('Stock adjusted.'));
    }

    /**
     * Archive rather than erase. The ledger behind an item is the record of
     * everything the kitchen ever used, so it outlives the item itself.
     */
    public function destroy(InventoryItem $item): RedirectResponse
    {
        $name = $item->displayName();

        $item->update(['is_active' => false]);
        $item->delete();

        app(Activity::class)->deleted($item, __('Stock item archived: :name', ['name' => $name]));

        return redirect()->route('inventory')
            ->with('status', __(':name archived. Its history is kept.', ['name' => $name]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?InventoryItem $item = null): array
    {
        return $request->validate([
            'inventory_category_id' => ['required', 'exists:inventory_categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', 'unique:inventory_items,sku'.($item ? ','.$item->id : '')],
            'unit' => ['required', 'string', 'max:24'],
            'current_quantity' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'count_frequency' => ['required', 'in:weekly,daily,per_event'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
