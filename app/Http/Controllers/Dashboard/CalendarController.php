<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The diary. A caterer's week is the unit of planning, so this is a month grid
 * with the jobs on it rather than a list — clashes and quiet weeks are both
 * things you have to see rather than read.
 */
class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->filled('month')
            ? CarbonImmutable::parse($request->string('month')->toString())->startOfMonth()
            : CarbonImmutable::today()->startOfMonth();

        $orders = Order::whereBetween('event_date', [$month->startOfMonth(), $month->endOfMonth()])
            ->with('payments')
            ->orderByRaw('serve_time IS NULL, serve_time')
            ->get()
            ->groupBy(fn (Order $order) => $order->event_date->toDateString());

        // A month grid always starts on the Monday on or before the first.
        $gridStart = $month->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);

        $days = [];
        for ($day = $gridStart; $day <= $gridEnd; $day = $day->addDay()) {
            $days[] = [
                'date' => $day,
                'in_month' => $day->month === $month->month,
                'orders' => $orders->get($day->toDateString(), collect()),
            ];
        }

        $monthOrders = $orders->flatten();

        return view('dashboard.calendar.index', [
            'month' => $month,
            'days' => $days,
            'jobCount' => $monthOrders->count(),
            'guestCount' => (int) $monthOrders->sum('guests'),
            'monthValue' => (int) $monthOrders->where('status', '!=', 'cancelled')->sum('total_amount'),
        ]);
    }
}
