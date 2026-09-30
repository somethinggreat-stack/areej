<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\CsvFile;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Monday summary: one week of orders (Monday to Sunday, by order date),
 * what they came to, what has come in, and who still owes what.
 *
 * Opens on last week, because that is the week being reviewed on Monday.
 */
class WeeklySummaryController extends Controller
{
    /** Unpaid this many days after the order date counts as overdue. */
    public const OVERDUE_AFTER_DAYS = 7;

    public function __construct(private readonly CsvFile $csv) {}

    public function index(Request $request): View
    {
        return view('dashboard.weekly-summary.index', $this->summary($this->weekStart($request)));
    }

    public function export(Request $request): StreamedResponse
    {
        $s = $this->summary($this->weekStart($request));

        $rows = collect([
            ['Week', $s['start']->format('Y-m-d').' to '.$s['end']->format('Y-m-d'), '', '', '', '', ''],
            ['Orders', $s['orderCount'], '', '', '', '', ''],
            ['Customers served', $s['customerCount'], '', '', '', '', ''],
            ['Total sales', self::pounds($s['sales']), '', '', '', '', ''],
            ['Paid against these orders', self::pounds($s['paidOnOrders']), '', '', '', '', ''],
            ['Still owed on these orders', self::pounds($s['owed']), '', '', '', '', ''],
            ['Money received this week (all orders)', self::pounds($s['receivedThisWeek']), '', '', '', '', ''],
            ['', '', '', '', '', '', ''],
            ['Date', 'Order', 'Customer', 'Phone', 'Total', 'Paid', 'Owed'],
        ])->concat($s['orders']->map(fn (Order $o) => [
            $o->event_date->format('Y-m-d'),
            $o->reference,
            $o->customer_name,
            $o->phone,
            self::pounds($o->total_amount),
            self::pounds($o->paidAmount()),
            self::pounds($o->balanceAmount()),
        ]));

        return $this->csv->download(
            sprintf('midland-weekly-summary-%s.csv', $s['start']->format('Y-m-d')),
            ['Midland Catering weekly summary', '', '', '', '', '', ''],
            $rows,
        );
    }

    private function weekStart(Request $request): CarbonImmutable
    {
        $day = $request->filled('week')
            ? CarbonImmutable::parse($request->string('week')->toString())
            : CarbonImmutable::today()->subWeek();

        return $day->startOfWeek(CarbonImmutable::MONDAY);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(CarbonImmutable $start): array
    {
        $end = $start->addDays(6);

        $orders = Order::with('payments')
            ->where('status', '!=', 'cancelled')
            ->whereDate('event_date', '>=', $start->toDateString())
            ->whereDate('event_date', '<=', $end->toDateString())
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $customers = $orders->groupBy(fn (Order $o) => CustomerController::keyFor($o))
            ->map(fn (Collection $group, string $key) => [
                'key' => $key,
                'name' => $group->first()->customer_name,
                'phone' => $group->firstWhere('phone', '!=', null)?->phone,
                'orders' => $group->count(),
                'total' => (int) $group->sum('total_amount'),
                'paid' => (int) $group->sum(fn (Order $o) => $o->paidAmount()),
                'owed' => (int) $group->sum(fn (Order $o) => $o->balanceAmount()),
            ])
            ->values();

        $received = OrderPayment::whereDate('paid_on', '>=', $start->toDateString())
            ->whereDate('paid_on', '<=', $end->toDateString())
            ->get()
            ->sum(fn (OrderPayment $p) => $p->kind === 'refund' ? -$p->amount : $p->amount);

        // Overdue from any week up to the end of this one, so nothing old slips.
        $overdue = Order::with('payments')
            ->where('status', '!=', 'cancelled')
            ->where('total_amount', '>', 0)
            ->whereDate('event_date', '<=', $end->min(CarbonImmutable::today()->subDays(self::OVERDUE_AFTER_DAYS))->toDateString())
            ->orderBy('event_date')
            ->get()
            ->filter(fn (Order $o) => $o->balanceAmount() > 0)
            ->values();

        return [
            'start' => $start,
            'end' => $end,
            'orders' => $orders,
            'orderCount' => $orders->count(),
            'customerCount' => $customers->count(),
            'sales' => (int) $orders->sum('total_amount'),
            'paidOnOrders' => (int) $orders->sum(fn (Order $o) => $o->paidAmount()),
            'owed' => (int) $orders->sum(fn (Order $o) => $o->balanceAmount()),
            'receivedThisWeek' => (int) $received,
            'paidInFull' => $customers->filter(fn (array $c) => $c['total'] > 0 && $c['owed'] === 0)->values(),
            'stillOwing' => $customers->filter(fn (array $c) => $c['owed'] > 0)->sortByDesc('owed')->values(),
            'noPrice' => $orders->filter(fn (Order $o) => (int) $o->total_amount === 0)->values(),
            'overdue' => $overdue,
            'overdueTotal' => (int) $overdue->sum(fn (Order $o) => $o->balanceAmount()),
        ];
    }

    private static function pounds(int $pence): string
    {
        return number_format($pence / 100, 2, '.', '');
    }
}
