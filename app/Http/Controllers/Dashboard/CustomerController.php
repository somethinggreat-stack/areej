<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Customers, worked out from their orders.
 *
 * There is no separate customer list to keep up to date: orders with the same
 * phone number (or, without one, the same name) are the same customer, so a
 * customer's balance is always exactly what their orders say.
 */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();
        $term = mb_strtolower(trim($request->string('q')->toString()));

        $customers = self::customers()
            ->when($filter === 'owing', fn (Collection $c) => $c->filter(fn (array $row) => $row['owed'] > 0))
            ->when($filter === 'paid', fn (Collection $c) => $c->filter(fn (array $row) => $row['owed'] === 0 && $row['total'] > 0))
            ->when($term !== '', fn (Collection $c) => $c->filter(fn (array $row) => str_contains(mb_strtolower($row['name'].' '.$row['phone']), $term)))
            ->sortByDesc(fn (array $row) => [$row['owed'], $row['last']->timestamp])
            ->values();

        return view('dashboard.customers.index', [
            'customers' => $customers,
            'filter' => $filter,
            'owedTotal' => $customers->sum('owed'),
        ]);
    }

    public function show(string $key): View
    {
        $orders = Order::with('payments')
            ->orderByDesc('event_date')
            ->get()
            ->filter(fn (Order $order) => self::keyFor($order) === $key)
            ->values();

        abort_if($orders->isEmpty(), 404);

        $live = $orders->where('status', '!=', 'cancelled');

        return view('dashboard.customers.show', [
            'name' => $orders->first()->customer_name,
            'phone' => $orders->firstWhere('phone', '!=', null)?->phone,
            'orders' => $orders,
            'payments' => $orders->flatMap(fn (Order $o) => $o->payments->map(fn ($p) => ['payment' => $p, 'order' => $o]))
                ->sortByDesc(fn (array $row) => $row['payment']->paid_on->timestamp)
                ->values(),
            'total' => (int) $live->sum('total_amount'),
            'paid' => (int) $live->sum(fn (Order $o) => $o->paidAmount()),
            'owed' => (int) $live->sum(fn (Order $o) => $o->balanceAmount()),
        ]);
    }

    /**
     * Phone digits when there are enough of them, otherwise the name.
     */
    public static function keyFor(Order $order): string
    {
        $digits = preg_replace('/\D+/', '', (string) $order->phone);

        return strlen($digits) >= 6 ? 'tel-'.$digits : 'name-'.mb_strtolower(trim($order->customer_name));
    }

    /**
     * @return Collection<int, array{key: string, name: string, phone: ?string, orders: int, total: int, paid: int, owed: int, last: CarbonInterface}>
     */
    public static function customers(): Collection
    {
        return Order::with('payments')
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('event_date')
            ->get()
            ->groupBy(fn (Order $order) => self::keyFor($order))
            ->map(fn (Collection $orders, string $key) => [
                'key' => $key,
                'name' => $orders->first()->customer_name,
                'phone' => $orders->firstWhere('phone', '!=', null)?->phone,
                'orders' => $orders->count(),
                'total' => (int) $orders->sum('total_amount'),
                'paid' => (int) $orders->sum(fn (Order $o) => $o->paidAmount()),
                'owed' => (int) $orders->sum(fn (Order $o) => $o->balanceAmount()),
                'last' => $orders->first()->event_date,
            ])
            ->values();
    }
}
