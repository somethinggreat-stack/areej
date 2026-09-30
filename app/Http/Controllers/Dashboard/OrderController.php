<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderDish;
use App\Models\OrderItem;
use App\Services\Activity;
use App\Services\IngredientPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Jobs on the books.
 *
 * Most orders arrive by phone or WhatsApp, so everything here is typed in by
 * hand and nothing is required beyond a name and a date — an order taken at
 * eight in the morning with no price agreed yet is still an order.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly Activity $activity,
        private readonly IngredientPlanner $planner,
    ) {}

    public function index(Request $request): View
    {
        $view = $request->string('view')->toString() ?: 'all';

        // Money paid in, less refunds, worked out in the query so "owing" and
        // "paid" can be filtered and paged rather than sorted out afterwards.
        $paid = '(select coalesce(sum(case when kind = \'refund\' then -amount else amount end), 0) from order_payments where order_payments.order_id = orders.id)';

        $orders = Order::query()
            ->when($request->filled('from'), fn ($q) => $q->whereDate('event_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('event_date', '<=', $request->date('to')))
            ->when($view === 'pending', fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
            ->when($view === 'completed', fn ($q) => $q->where('status', 'completed'))
            ->when($view === 'owing', fn ($q) => $q->where('status', '!=', 'cancelled')->whereRaw("total_amount > {$paid}"))
            ->when($view === 'paid', fn ($q) => $q->where('status', '!=', 'cancelled')->where('total_amount', '>', 0)->whereRaw("total_amount <= {$paid}"))
            ->with(['payments', 'takenBy'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($q) => $q->where('customer_name', 'like', $term)
                    ->orWhere('reference', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('venue', 'like', $term));
            })
            ->when($view === 'upcoming', fn ($q) => $q->whereDate('event_date', '>=', today())
                ->whereNotIn('status', ['cancelled', 'completed']))
            ->when($view === 'past', fn ($q) => $q->whereDate('event_date', '<', today()))
            ->when($view === 'cancelled', fn ($q) => $q->where('status', 'cancelled'))
            ->when($view === 'unpaid', fn ($q) => $q->where('total_amount', '>', 0)->whereNot('status', 'cancelled'))
            ->orderBy('event_date', in_array($view, ['upcoming', 'unpaid'], true) ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $owing = Order::owing()->with('payments')->get()
            ->filter(fn (Order $o) => $o->balanceAmount() > 0);

        return view('dashboard.orders.index', [
            'orders' => $orders,
            'view' => $view,
            'owingTotal' => $owing->sum(fn (Order $o) => $o->balanceAmount()),
            'owingCount' => $owing->count(),
            'thisWeek' => Order::upcoming()->whereBetween('event_date', [today(), today()->addDays(7)])->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('dashboard.orders.form', [
            'order' => new Order([
                'event_date' => today()->addWeek(),
                'service_style' => 'not_set',
                'status' => 'draft',
                'source' => 'phone',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['taken_by'] = $request->user()->id;

        $order = Order::create($data);

        return redirect()->route('orders.show', $order)
            ->with('status', __('Order :ref created.', ['ref' => $order->reference]));
    }

    public function show(Order $order): View
    {
        $order->load([
            'items', 'payments.recorder', 'takenBy', 'enquiry', 'quotes',
            'shifts.staff', 'equipment.equipmentItem', 'expenses',
            'wasteLogs.item', 'tasks.assignee', 'dishes.dish.ingredients.inventoryItem',
        ]);

        return view('dashboard.orders.show', [
            'order' => $order,
            'items' => InventoryItem::where('is_active', true)->orderBy('name_en')->get(),
            'dishes' => Dish::active()->inMenuOrder()->get(),
            'requirements' => $this->planner->requirementsFor($order),
            'costs' => $order->costBreakdown(),
        ]);
    }

    /**
     * A printable invoice. Rendered from the same records as the screen, so it
     * can never show a different total from the one on the order.
     */
    public function invoice(Order $order): View
    {
        return view('dashboard.orders.invoice', [
            'order' => $order->load(['items', 'payments']),
        ]);
    }

    public function destroy(Order $order): RedirectResponse
    {
        if ($order->paidAmount() > 0) {
            return back()->withErrors(['form' => __('Money has been taken against this job. Cancel it instead of deleting it.')]);
        }

        $reference = $order->reference;
        $order->delete();

        $this->activity->deleted($order, __('Job :ref deleted', ['ref' => $reference]));

        return redirect()->route('orders')->with('status', __('Job :ref deleted.', ['ref' => $reference]));
    }

    /* ------------------------------------------------------- what to cook */

    public function storeDish(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'dish_id' => ['required', 'exists:dishes,id'],
            'guests' => ['required', 'integer', 'min:1', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Adding the same dish twice corrects the head count rather than
        // creating a second line that would double the ingredient forecast.
        $order->dishes()->updateOrCreate(
            ['dish_id' => $data['dish_id']],
            ['guests' => $data['guests'], 'notes' => $data['notes'] ?? null],
        );

        return back()->with('status', __('Menu updated. The ingredient list has been recalculated.'));
    }

    public function destroyDish(OrderDish $orderDish): RedirectResponse
    {
        if ($orderDish->ingredients_deducted) {
            return back()->withErrors(['form' => __('This dish has already had its ingredients taken off the shelf.')]);
        }

        $orderDish->delete();

        return back()->with('status', __('Dish removed from the job.'));
    }

    /**
     * Takes the planned ingredients off the shelf once the job has been cooked.
     * Refuses on a shortfall rather than driving stock negative.
     */
    public function deductIngredients(Request $request, Order $order): RedirectResponse
    {
        $result = $this->planner->deduct($order, $request->user());

        if ($result['blocked'] !== []) {
            return back()->withErrors(['form' => __('Not enough stock for: :items. Buy them in or correct the counts first.', [
                'items' => implode(', ', $result['blocked']),
            ])]);
        }

        if ($result['deducted'] === 0) {
            return back()->with('warning', __('Nothing to deduct — either the dishes have no recipes yet, or this was already done.'));
        }

        $this->activity->log('stock', __('Ingredients deducted for :ref', ['ref' => $order->reference]), $order);

        return back()->with('status', trans_choice(
            '{1}1 ingredient taken off the shelf.|[2,*]:count ingredients taken off the shelf.',
            $result['deducted'],
            ['count' => $result['deducted']],
        ));
    }

    /* ------------------------------------------------------------- tasks */

    public function storeTask(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'stage' => ['required', 'in:prep,cook,pack,transport,serve,clear'],
            'due_offset_hours' => ['required', 'integer', 'min:0', 'max:336'],
            'assigned_to' => ['nullable', 'exists:staff_profiles,id'],
            'detail' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->tasks()->create([
            ...$data,
            'position' => (int) $order->tasks()->max('position') + 1,
        ]);

        return back()->with('status', __('Task added.'));
    }

    /**
     * The standard run of a job, so nobody builds the same checklist by hand
     * for every wedding. Offsets are hours before serving.
     */
    public function seedTasks(Order $order): RedirectResponse
    {
        if ($order->tasks()->exists()) {
            return back()->with('warning', __('This job already has a checklist.'));
        }

        $standard = [
            ['title' => __('Confirm final numbers with the customer'), 'stage' => 'prep', 'due_offset_hours' => 72],
            ['title' => __('Check stock and order anything short'), 'stage' => 'prep', 'due_offset_hours' => 72],
            ['title' => __('Marinate meat'), 'stage' => 'prep', 'due_offset_hours' => 18],
            ['title' => __('Prep vegetables and masala'), 'stage' => 'prep', 'due_offset_hours' => 14],
            ['title' => __('Cook'), 'stage' => 'cook', 'due_offset_hours' => 6],
            ['title' => __('Pack trays, count chafing dishes'), 'stage' => 'pack', 'due_offset_hours' => 3],
            ['title' => __('Load the van'), 'stage' => 'transport', 'due_offset_hours' => 2],
            ['title' => __('Set up at the venue'), 'stage' => 'serve', 'due_offset_hours' => 1],
            ['title' => __('Collect equipment and clear down'), 'stage' => 'clear', 'due_offset_hours' => -3],
        ];

        foreach ($standard as $index => $task) {
            $order->tasks()->create($task + ['position' => $index]);
        }

        return back()->with('status', __('Standard checklist added.'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $order->update($this->validated($request));

        return back()->with('status', __('Order updated.'));
    }

    public function storeItem(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'string', 'max:24'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $order->items()->create([
            ...$data,
            // Prices are entered in pounds and stored in pence.
            'unit_price' => (int) round($data['unit_price'] * 100),
            'position' => (int) $order->items()->max('position') + 1,
        ]);

        $this->syncTotalFromItems($order);

        return back()->with('status', __('Line added.'));
    }

    public function destroyItem(OrderItem $item): RedirectResponse
    {
        $order = $item->order;
        $item->delete();

        $this->syncTotalFromItems($order);

        return back()->with('status', __('Line removed.'));
    }

    /**
     * Keeps the headline total in step with the lines, but only while the lines
     * are the source of the price. An order priced as a single agreed figure
     * with no lines keeps whatever was typed in.
     */
    private function syncTotalFromItems(Order $order): void
    {
        $order->refresh()->load('items');

        if ($order->items->isNotEmpty()) {
            $order->update(['total_amount' => $order->itemsTotal()]);
        }
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'order_type' => ['nullable', 'string', 'max:64'],
            'event_date' => ['required', 'date'],
            'serve_time' => ['nullable', 'date_format:H:i'],
            'venue' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:500'],
            'guests' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'service_style' => ['required', 'in:delivery,collection,delivered_and_served,full_buffet,not_set'],
            'staff_required' => ['nullable', 'integer', 'min:0', 'max:200'],
            'menu_notes' => ['nullable', 'string', 'max:5000'],
            'dietary' => ['nullable', 'string', 'max:1000'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_due' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,confirmed,in_preparation,delivered,completed,cancelled'],
            'source' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        // The price fields are only on the form for management and the owner.
        // For anyone else they are left out entirely, so saving a job's details
        // can never wipe a price they were not shown.
        if ($request->user()->canSeeFinancials()) {
            $data['total_amount'] = (int) round((float) ($data['total_amount'] ?? 0) * 100);
            $data['deposit_due'] = (int) round((float) ($data['deposit_due'] ?? 0) * 100);
        } else {
            unset($data['total_amount'], $data['deposit_due']);
        }

        $data['guests'] ??= 0;
        $data['staff_required'] ??= 0;

        return $data;
    }
}
