<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(private readonly Activity $activity) {}

    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();

        return view('dashboard.suppliers.index', [
            'suppliers' => Supplier::withCount('inventoryItems')
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $term = '%'.$request->string('q')->toString().'%';
                    $query->where(fn ($q) => $q->where('name', 'like', $term)
                        ->orWhere('contact_name', 'like', $term)
                        ->orWhere('phone', 'like', $term));
                })
                ->when($filter !== 'archived', fn ($q) => $q->where('is_active', true))
                ->when($filter === 'archived', fn ($q) => $q->where('is_active', false))
                ->orderBy('name')
                ->paginate(24)
                ->withQueryString(),
            'filter' => $filter,
        ]);
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['payments.recorder', 'purchaseOrders.lines']);

        // What we owe them: everything received, less everything paid.
        $received = (int) round(
            $supplier->purchaseOrders
                ->whereIn('status', ['part_received', 'received'])
                ->sum(fn (PurchaseOrder $order) => $order->lines->sum(
                    fn ($line) => (float) $line->quantity_received * (float) ($line->unit_cost ?? 0)
                )) * 100
        );

        $paid = (int) $supplier->payments->sum('amount');

        return view('dashboard.suppliers.show', [
            'supplier' => $supplier,
            'items' => $supplier->inventoryItems()->orderBy('name_en')->get(),
            'received' => $received,
            'paid' => $paid,
            'owed' => max(0, $received - $paid),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));

        $this->activity->created($supplier, __('Supplier added: :name', ['name' => $supplier->name]));

        return back()->with('status', __('Supplier added.'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $before = $supplier->getOriginal();
        $supplier->update($this->validated($request));

        $this->activity->updated($supplier, __('Supplier updated: :name', ['name' => $supplier->name]), $before);

        return back()->with('status', __('Supplier updated.'));
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $open = $supplier->purchaseOrders()->outstanding()->count();

        if ($open > 0) {
            return back()->withErrors(['form' => trans_choice(
                '{1}This supplier has 1 order still open. Close it first.|[2,*]This supplier has :count orders still open. Close them first.',
                $open,
                ['count' => $open],
            )]);
        }

        $name = $supplier->name;

        // Archived rather than erased: their purchase history is the record of
        // what the kitchen bought and at what price.
        $supplier->update(['is_active' => false]);
        $supplier->delete();

        $this->activity->deleted($supplier, __('Supplier archived: :name', ['name' => $name]));

        return redirect()->route('suppliers')->with('status', __(':name archived.', ['name' => $name]));
    }

    public function storePayment(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'method' => ['required', 'in:cash,bank_transfer,card,cheque,other'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'reference' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $supplier->payments()->create([
            ...$data,
            'amount' => (int) round((float) $data['amount'] * 100),
            'recorded_by' => $request->user()->id,
        ]);

        $this->activity->log('paid', __('Paid :name £:amount', [
            'name' => $supplier->name,
            'amount' => number_format((float) $data['amount'], 2),
        ]), $supplier);

        return back()->with('status', __('Payment recorded.'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'order_method' => ['required', 'in:phone,whatsapp,email,online,in_person'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);
    }
}
