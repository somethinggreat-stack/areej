<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The simple order book.
 *
 * Orders are written in a notebook during the week and typed in on Monday or
 * Tuesday, so one page holds the whole order: who, when, what (free-text items
 * with a quantity and unit), how much, and what has been paid. "Save and add
 * the next one" keeps the date, so a week of orders goes in one after another.
 */
class OrderBookController extends Controller
{
    /** Units the kitchen counts in. */
    public const UNITS = ['kg' => 'KG', 'portion' => 'Portion', 'pieces' => 'Pieces', 'number' => 'Number'];

    /** How many empty item rows a new order starts with. */
    private const BLANK_ROWS = 6;

    public function __construct(private readonly Activity $activity) {}

    public function create(Request $request): View
    {
        $order = new Order([
            'event_date' => $request->date('date') ?? today(),
            'status' => 'confirmed',
        ]);

        return view('dashboard.order-book.form', [
            'order' => $order,
            'rows' => array_fill(0, self::BLANK_ROWS, ['description' => '', 'quantity' => '', 'unit' => 'portion', 'price' => '']),
            'units' => self::UNITS,
            'recent' => Order::with('payments')->latest('id')->limit(8)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $order = DB::transaction(function () use ($data, $request): Order {
            $order = Order::create($this->orderFields($data) + [
                'service_style' => 'not_set',
                'source' => 'notebook',
                'taken_by' => $request->user()->id,
            ]);

            $this->saveItems($order, $data);

            if ((float) ($data['paid_now'] ?? 0) > 0) {
                $this->recordPayment($order, $data, $request);
            }

            return $order;
        });

        $this->activity->created($order, __('Order :ref entered for :name', ['ref' => $order->reference, 'name' => $order->customer_name]));

        $message = __('Order :ref saved for :name.', ['ref' => $order->reference, 'name' => $order->customer_name]);

        return $request->boolean('add_another')
            ? redirect()->route('order-book.create', ['date' => $order->event_date->toDateString()])->with('status', $message)
            : redirect()->route('order-book.edit', $order)->with('status', $message);
    }

    public function edit(Order $order): View
    {
        $order->load(['items', 'payments.recorder']);

        $rows = $order->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => $item->quantity === null ? '' : rtrim(rtrim((string) $item->quantity, '0'), '.'),
            'unit' => array_key_exists((string) $item->unit, self::UNITS) ? $item->unit : ($item->unit ?: 'portion'),
            'price' => $item->unit_price ? number_format($item->unit_price / 100, 2, '.', '') : '',
        ])->all();

        // Always a few spare rows to add to.
        $rows = array_merge($rows, array_fill(0, max(3, self::BLANK_ROWS - count($rows)), ['description' => '', 'quantity' => '', 'unit' => 'portion', 'price' => '']));

        return view('dashboard.order-book.form', [
            'order' => $order,
            'rows' => $rows,
            'units' => self::UNITS,
            'recent' => collect(),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $this->validated($request);
        $before = $order->getOriginal();

        DB::transaction(function () use ($order, $data, $request): void {
            $order->update($this->orderFields($data));
            $this->saveItems($order, $data);

            if ((float) ($data['paid_now'] ?? 0) > 0) {
                $this->recordPayment($order, $data, $request);
            }
        });

        $this->activity->updated($order, __('Order :ref updated', ['ref' => $order->reference]), $before);

        return redirect()->route('order-book.edit', $order)->with('status', __('Saved.'));
    }

    /**
     * Every payment received, newest first, for a date range (this month by default).
     */
    public function payments(Request $request): View
    {
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();

        $payments = OrderPayment::with(['order' => fn ($q) => $q->withTrashed(), 'recorder'])
            ->whereDate('paid_on', '>=', $from->toDateString())
            ->whereDate('paid_on', '<=', $to->toDateString())
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->get();

        return view('dashboard.order-book.payments', [
            'payments' => $payments,
            'from' => $from,
            'to' => $to,
            'received' => (int) $payments->sum(fn (OrderPayment $p) => $p->kind === 'refund' ? -$p->amount : $p->amount),
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'venue_address' => ['nullable', 'string', 'max:500'],
            'event_date' => ['required', 'date'],
            'order_status' => ['required', 'in:pending,completed,cancelled'],
            'items' => ['array'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'items.*.unit' => ['nullable', 'string', 'max:24'],
            'items.*.price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'total' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'paid_now' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'paid_method' => ['nullable', 'in:cash,bank_transfer,card,cheque,other'],
            'paid_on' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function orderFields(array $data): array
    {
        return [
            'customer_name' => $data['customer_name'],
            'phone' => $data['phone'] ?? null,
            'venue_address' => $data['venue_address'] ?? null,
            'event_date' => $data['event_date'],
            'status' => ['pending' => 'confirmed', 'completed' => 'completed', 'cancelled' => 'cancelled'][$data['order_status']],
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * Replaces the order's lines with the rows typed. The total is what was
     * typed in "Order amount", or the priced lines added up when it is blank.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveItems(Order $order, array $data): void
    {
        $order->items()->delete();

        $position = 0;
        foreach ($data['items'] ?? [] as $row) {
            if (blank($row['description'] ?? null)) {
                continue;
            }

            $quantity = (float) ($row['quantity'] ?? 0) ?: 1;
            $price = (int) round((float) ($row['price'] ?? 0) * 100);

            $order->items()->create([
                'description' => $row['description'],
                'quantity' => $quantity,
                'unit' => $row['unit'] ?? 'portion',
                'unit_price' => $price,
                'line_total' => (int) round($quantity * $price),
                'position' => $position++,
            ]);
        }

        $typed = $data['total'] ?? null;
        $order->update([
            'total_amount' => filled($typed) ? (int) round((float) $typed * 100) : (int) $order->items()->sum('line_total'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function recordPayment(Order $order, array $data, Request $request): void
    {
        $amount = (int) round((float) $data['paid_now'] * 100);
        $order->load('payments');

        OrderPayment::create([
            'order_id' => $order->id,
            'amount' => $amount,
            'method' => $data['paid_method'] ?? 'cash',
            'kind' => $order->paidAmount() + $amount >= $order->total_amount ? 'balance' : 'part_payment',
            'paid_on' => $data['paid_on'] ?? today()->toDateString(),
            'recorded_by' => $request->user()->id,
        ]);
    }
}
