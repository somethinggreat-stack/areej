<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EquipmentAssignment;
use App\Models\EquipmentItem;
use App\Models\EquipmentMaintenance;
use App\Models\Order;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Chafing dishes, serving dishes and crockery going out to jobs and coming
 * back. What is out is always derived from open assignments, never stored.
 */
class EquipmentController extends Controller
{
    public function index(): View
    {
        return view('dashboard.equipment.index', [
            'items' => EquipmentItem::where('is_active', true)->orderBy('category')->orderBy('name_en')->get(),
            'out' => EquipmentAssignment::stillOut()
                ->with(['equipmentItem', 'order'])
                ->get()
                ->sortBy(fn (EquipmentAssignment $a) => $a->order->event_date),
            'orders' => Order::upcoming()->limit(40)->get(),
            'maintenance' => EquipmentMaintenance::with('equipmentItem')
                ->open()
                ->orderByRaw('due_on IS NULL, due_on')
                ->get(),
        ]);
    }

    public function update(Request $request, EquipmentItem $equipmentItem): RedirectResponse
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'quantity_owned' => ['required', 'integer', 'min:0', 'max:100000'],
            'replacement_cost' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $out = $equipmentItem->quantityOut();

        if ($data['quantity_owned'] < $out) {
            return back()->withErrors([
                'quantity_owned' => __('There are :n already out on jobs, so the total owned cannot be lower than that.', ['n' => $out]),
            ])->withInput();
        }

        $data['replacement_cost'] = (int) round((float) ($data['replacement_cost'] ?? 0) * 100);
        $equipmentItem->update($data);

        return back()->with('status', __('Equipment updated.'));
    }

    public function destroy(EquipmentItem $equipmentItem): RedirectResponse
    {
        if ($equipmentItem->quantityOut() > 0) {
            return back()->withErrors(['form' => __('Some of this is still out on a job. Book it back in first.')]);
        }

        $name = $equipmentItem->displayName();

        $equipmentItem->update(['is_active' => false]);
        $equipmentItem->delete();

        app(Activity::class)->deleted($equipmentItem, __('Equipment archived: :name', ['name' => $name]));

        return back()->with('status', __(':name archived.', ['name' => $name]));
    }

    public function storeMaintenance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipment_item_id' => ['required', 'exists:equipment_items,id'],
            'type' => ['required', 'in:service,repair,inspection,replacement'],
            'quantity' => ['required', 'integer', 'min:1'],
            'due_on' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'provider' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = EquipmentItem::findOrFail($data['equipment_item_id']);

        if ($data['quantity'] > $item->quantityAvailable()) {
            return back()->withErrors([
                'quantity' => __('Only :n of :name are here to work on.', [
                    'n' => $item->quantityAvailable(),
                    'name' => $item->displayName(),
                ]),
            ])->withInput();
        }

        EquipmentMaintenance::create([
            ...$data,
            'cost' => (int) round((float) ($data['cost'] ?? 0) * 100),
            'status' => 'scheduled',
            'logged_by' => $request->user()->id,
        ]);

        return back()->with('status', __('Booked in for repair. It will not show as available until it is back.'));
    }

    public function completeMaintenance(Request $request, EquipmentMaintenance $maintenance): RedirectResponse
    {
        $maintenance->update([
            'status' => 'completed',
            'completed_on' => today(),
        ]);

        return back()->with('status', __('Back in service.'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Two jobs on one screen: adding kit to the register, and sending kit
        // out to a job. They are separated by which fields arrived.
        if ($request->filled('order_id')) {
            return $this->checkOut($request);
        }

        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'asset_tag' => ['nullable', 'string', 'max:64', 'unique:equipment_items,asset_tag'],
            'quantity_owned' => ['required', 'integer', 'min:0'],
            'replacement_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['replacement_cost'] = (int) round((float) ($data['replacement_cost'] ?? 0) * 100);

        EquipmentItem::create($data);

        return back()->with('status', __('Equipment added.'));
    }

    private function checkOut(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'equipment_item_id' => ['required', 'exists:equipment_items,id'],
            'quantity_out' => ['required', 'integer', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $item = EquipmentItem::findOrFail($data['equipment_item_id']);

        if ($data['quantity_out'] > $item->quantityAvailable()) {
            return back()->withErrors([
                'quantity_out' => __('Only :n of :name are available — the rest are already out.', [
                    'n' => $item->quantityAvailable(),
                    'name' => $item->displayName(),
                ]),
            ])->withInput();
        }

        EquipmentAssignment::create([
            ...$data,
            'checked_out_at' => now(),
            'checked_out_by' => $request->user()->id,
        ]);

        return back()->with('status', __('Sent out with the job.'));
    }

    public function returnItems(Request $request, EquipmentAssignment $assignment): RedirectResponse
    {
        $data = $request->validate([
            'quantity_returned' => ['required', 'integer', 'min:0'],
            'quantity_lost' => ['nullable', 'integer', 'min:0'],
            'quantity_damaged' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $accounted = $data['quantity_returned'] + ($data['quantity_lost'] ?? 0) + ($data['quantity_damaged'] ?? 0);

        if ($accounted > $assignment->quantity_out) {
            return back()->withErrors([
                'quantity_returned' => __('That is more than the :n that went out.', ['n' => $assignment->quantity_out]),
            ])->withInput();
        }

        $assignment->update([
            ...$data,
            'quantity_lost' => $data['quantity_lost'] ?? 0,
            'quantity_damaged' => $data['quantity_damaged'] ?? 0,
            'returned_at' => now(),
            'returned_by' => $request->user()->id,
        ]);

        $missing = $assignment->unaccountedFor();

        return back()->with('status', $missing > 0
            ? __('Booked back in. :n still unaccounted for.', ['n' => $missing])
            : __('Booked back in.'));
    }
}
