<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\Quote;
use App\Services\OperationsFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The control centre.
 *
 * Not a wall of counters: everything here is either happening today, overdue,
 * or costing money, and every figure links to the screen that resolves it.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly OperationsFeed $feed) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $today = $this->feed->today();
        $upcoming = $this->feed->upcoming(14);

        return view('dashboard.index', [
            'today' => $today,
            'upcoming' => $upcoming->take(6),
            'alerts' => $this->feed->alerts(),

            'money' => $user->canSeeFinancials()
                ? $this->feed->financials($monthStart, $monthEnd)
                : null,

            'counts' => [
                'today_jobs' => $today->count(),
                'today_guests' => (int) $today->sum('guests'),
                'upcoming_jobs' => $upcoming->count(),
                'on_site' => AttendanceRecord::open()->count(),
                'low_stock' => InventoryItem::needsReorder()->count(),
                'open_quotes' => Quote::open()->count(),
            ],

            // The next thing anyone has to actually do, whatever it is.
            'nextTasks' => OrderTask::outstanding()
                ->with(['order', 'assignee'])
                ->whereHas('order', fn ($q) => $q->whereBetween('event_date', [today(), today()->addDays(3)])
                    ->whereNotIn('status', ['cancelled', 'completed']))
                ->get()
                ->sortBy(fn (OrderTask $t) => $t->dueAt()?->timestamp ?? PHP_INT_MAX)
                ->take(8),

            'unpaid' => $user->canSeeFinancials()
                ? Order::owing()->with('payments')->get()
                    ->filter(fn (Order $o) => $o->balanceAmount() > 0)
                    ->sortBy('event_date')
                    ->take(5)
                : collect(),

            'recentQuotes' => Quote::awaitingReply()
                ->orderBy('sent_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
