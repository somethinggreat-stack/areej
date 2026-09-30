<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Quote;
use App\Models\StockMovement;
use App\Models\WasteLog;
use App\Services\CsvFile;
use App\Services\OperationsFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporting.
 *
 * Every figure here is derived from the operational records rather than stored,
 * so a report can never quietly disagree with the screen it came from.
 */
class ReportController extends Controller
{
    public function __construct(private readonly OperationsFeed $feed) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $orders = Order::whereBetween('event_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->with(['payments', 'expenses', 'shifts', 'wasteLogs'])
            ->orderBy('event_date')
            ->get();

        return view('dashboard.reports.index', [
            'from' => $from,
            'to' => $to,
            'preset' => $request->string('preset')->toString() ?: 'month',
            'financials' => $this->feed->financials($from, $to),
            'orders' => $orders,
            'jobProfit' => $this->jobProfit($orders),
            'expensesByCategory' => Expense::between($from, $to)->get()
                ->groupBy('category')
                ->map(fn (Collection $g) => (int) $g->sum('amount'))
                ->sortDesc(),
            'wasteByReason' => WasteLog::whereBetween('wasted_on', [$from, $to])->get()
                ->groupBy('reason')
                ->map(fn (Collection $g) => round($g->sum(fn (WasteLog $w) => $w->cost()), 2))
                ->sortDesc(),
            'topDishes' => $this->topDishes($from, $to),
            'stockValue' => (int) round(
                InventoryItem::where('is_active', true)->get()->sum(fn (InventoryItem $i) => $i->stockValue()) * 100
            ),
            'quoteStats' => $this->quoteStats($from, $to),
            'busiestDays' => $orders->groupBy(fn (Order $o) => $o->event_date->format('l'))
                ->map(fn (Collection $g) => $g->count())
                ->sortDesc(),
        ]);
    }

    /**
     * Margin per job, worst first — the ones losing money are the point.
     *
     * @param  Collection<int, Order>  $orders
     * @return Collection<int, array<string, mixed>>
     */
    private function jobProfit(Collection $orders): Collection
    {
        return $orders
            ->filter(fn (Order $o) => $o->total_amount > 0)
            ->map(function (Order $order): array {
                $costs = $order->costBreakdown();

                return [
                    'order' => $order,
                    'revenue' => $order->total_amount,
                    'costs' => $costs,
                    'profit' => $order->total_amount - $costs['total'],
                    'margin' => $order->marginPercent(),
                ];
            })
            ->sortBy('margin')
            ->values();
    }

    /**
     * @return Collection<int, array{name: string, guests: int, jobs: int}>
     */
    private function topDishes(Carbon $from, Carbon $to): Collection
    {
        return Order::whereBetween('event_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->with('dishes.dish')
            ->get()
            ->flatMap(fn (Order $o) => $o->dishes)
            ->groupBy(fn ($orderDish) => $orderDish->dish?->displayName() ?? __('Unknown'))
            ->map(fn (Collection $rows, string $name) => [
                'name' => $name,
                'guests' => (int) $rows->sum('guests'),
                'jobs' => $rows->count(),
            ])
            ->sortByDesc('guests')
            ->take(10)
            ->values();
    }

    /**
     * @return array{sent: int, accepted: int, declined: int, value: int, win_rate: int|null}
     */
    private function quoteStats(Carbon $from, Carbon $to): array
    {
        $quotes = Quote::whereBetween('created_at', [$from, $to->copy()->endOfDay()])->get();
        $decided = $quotes->whereIn('status', ['accepted', 'declined', 'expired'])->count();

        return [
            'sent' => $quotes->whereNotNull('sent_at')->count(),
            'accepted' => $quotes->where('status', 'accepted')->count(),
            'declined' => $quotes->whereIn('status', ['declined', 'expired'])->count(),
            'value' => (int) $quotes->where('status', 'accepted')->sum('total'),
            'win_rate' => $decided === 0
                ? null
                : (int) round($quotes->where('status', 'accepted')->count() / $decided * 100),
        ];
    }

    /**
     * CSV export. Streamed rather than built in memory so a long date range
     * cannot exhaust the process.
     */
    public function export(Request $request, string $report): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        $builders = [
            'jobs' => fn () => $this->exportJobs($from, $to),
            'expenses' => fn () => $this->exportExpenses($from, $to),
            'waste' => fn () => $this->exportWaste($from, $to),
            'stock' => fn () => $this->exportStock(),
            'movements' => fn () => $this->exportMovements($from, $to),
        ];

        abort_unless(isset($builders[$report]), 404);

        [$headers, $rows] = $builders[$report]();
        $filename = sprintf('midland-%s-%s-to-%s.csv', $report, $from->format('Y-m-d'), $to->format('Y-m-d'));

        return app(CsvFile::class)->download($filename, $headers, $rows);
    }

    private function exportJobs(Carbon $from, Carbon $to): array
    {
        $rows = Order::whereBetween('event_date', [$from, $to])
            ->with(['payments', 'expenses', 'shifts', 'wasteLogs'])
            ->orderBy('event_date')
            ->get()
            ->map(function (Order $order): array {
                $costs = $order->costBreakdown();

                return [
                    $order->reference,
                    $order->event_date->format('Y-m-d'),
                    $order->customer_name,
                    $order->venue,
                    $order->guests,
                    $order->statusLabel(),
                    number_format($order->total_amount / 100, 2, '.', ''),
                    number_format($order->paidAmount() / 100, 2, '.', ''),
                    number_format($order->balanceAmount() / 100, 2, '.', ''),
                    number_format($costs['ingredients'] / 100, 2, '.', ''),
                    number_format($costs['labour'] / 100, 2, '.', ''),
                    number_format($costs['waste'] / 100, 2, '.', ''),
                    number_format($costs['other'] / 100, 2, '.', ''),
                    number_format(($order->total_amount - $costs['total']) / 100, 2, '.', ''),
                    $order->marginPercent() ?? '',
                ];
            });

        return [[
            'Reference', 'Date', 'Customer', 'Venue', 'Guests', 'Status',
            'Total', 'Paid', 'Outstanding',
            'Ingredients', 'Labour', 'Waste', 'Other costs', 'Profit', 'Margin %',
        ], $rows];
    }

    private function exportExpenses(Carbon $from, Carbon $to): array
    {
        $rows = Expense::between($from, $to)->with(['order', 'supplier'])->orderBy('spent_on')->get()
            ->map(fn (Expense $e) => [
                $e->spent_on->format('Y-m-d'),
                $e->description,
                $e->categoryLabel(),
                $e->supplier?->name,
                $e->order?->reference,
                number_format($e->amount / 100, 2, '.', ''),
                $e->reference,
            ]);

        return [['Date', 'Description', 'Category', 'Supplier', 'Job', 'Amount', 'Reference'], $rows];
    }

    private function exportWaste(Carbon $from, Carbon $to): array
    {
        $rows = WasteLog::whereBetween('wasted_on', [$from, $to])->with(['item', 'order'])->orderBy('wasted_on')->get()
            ->map(fn (WasteLog $w) => [
                $w->wasted_on->format('Y-m-d'),
                $w->typeLabel(),
                $w->item?->name_en ?? $w->description,
                qty($w->quantity),
                $w->unitLabel(),
                $w->reasonLabel(),
                number_format($w->cost(), 2, '.', ''),
                $w->order?->reference,
            ]);

        return [['Date', 'Type', 'Item', 'Quantity', 'Unit', 'Reason', 'Cost', 'Job'], $rows];
    }

    private function exportStock(): array
    {
        $rows = InventoryItem::with(['category', 'supplier'])->orderBy('name_en')->get()
            ->map(fn (InventoryItem $i) => [
                $i->name_en,
                $i->name_ur,
                $i->category?->name_en,
                $i->supplier?->name,
                qty($i->current_quantity),
                $i->unit,
                qty($i->reorder_level),
                $i->unit_cost,
                number_format($i->stockValue(), 2, '.', ''),
                $i->isLowStock() ? 'YES' : '',
            ]);

        return [['Item', 'Item (Urdu)', 'Category', 'Supplier', 'On hand', 'Unit', 'Reorder at', 'Unit cost', 'Value', 'Low'], $rows];
    }

    private function exportMovements(Carbon $from, Carbon $to): array
    {
        $rows = StockMovement::with(['item', 'recorder'])
            ->whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->orderBy('created_at')
            ->get()
            ->map(fn (StockMovement $m) => [
                $m->created_at->format('Y-m-d H:i'),
                $m->item?->name_en,
                $m->typeLabel(),
                qty($m->quantity_change),
                qty($m->quantity_after),
                $m->recorder?->name,
                $m->notes,
            ]);

        return [['When', 'Item', 'Type', 'Change', 'Balance after', 'By', 'Notes'], $rows];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            return [$request->date('from')->startOfDay(), $request->date('to')->endOfDay()];
        }

        return match ($request->string('preset')->toString()) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
