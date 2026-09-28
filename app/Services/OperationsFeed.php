<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Enquiry;
use App\Models\EquipmentAssignment;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\WasteLog;
use Illuminate\Support\Collection;

/**
 * Everything the business needs to look at right now, in one place.
 *
 * The dashboard, the alert badge and the diary all read from here so they can
 * never disagree about what is urgent. Every alert carries the link to the
 * screen that resolves it — a number nobody can act on is decoration.
 */
class OperationsFeed
{
    /**
     * Jobs happening today, in serving order.
     *
     * @return Collection<int, Order>
     */
    public function today(): Collection
    {
        return Order::whereDate('event_date', today())
            ->whereNotIn('status', ['cancelled'])
            ->with(['payments', 'tasks', 'shifts'])
            ->orderByRaw('serve_time IS NULL, serve_time')
            ->get();
    }

    /**
     * @return Collection<int, Order>
     */
    public function upcoming(int $days = 14): Collection
    {
        return Order::whereBetween('event_date', [today()->addDay(), today()->addDays($days)])
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with('payments')
            ->orderBy('event_date')
            ->get();
    }

    /**
     * Things that need a human. Ordered by how much they cost to ignore.
     *
     * @return Collection<int, array{
     *     tone: string, icon: string, title: string, detail: string, href: string, count: int
     * }>
     */
    public function alerts(): Collection
    {
        $alerts = [];

        /* --- money already earned but not collected ---------------------- */
        $owed = Order::owing()->with('payments')->get()
            ->filter(fn (Order $o) => $o->balanceAmount() > 0 && $o->event_date->isPast());

        if ($owed->isNotEmpty()) {
            $alerts[] = [
                'tone' => 'bad',
                'icon' => 'pound',
                'title' => __('Unpaid after the event'),
                'detail' => __(':amount across :n jobs', [
                    'amount' => '£'.number_format($owed->sum(fn (Order $o) => $o->balanceAmount()) / 100, 2),
                    'n' => $owed->count(),
                ]),
                'href' => route('orders', ['view' => 'unpaid']),
                'count' => $owed->count(),
            ];
        }

        /* --- stock that will stop a job ---------------------------------- */
        $low = InventoryItem::needsReorder()->count();

        if ($low > 0) {
            $alerts[] = [
                'tone' => 'warn',
                'icon' => 'box',
                'title' => __('Stock below reorder level'),
                'detail' => trans_choice('{1}1 item needs ordering|[2,*]:count items need ordering', $low, ['count' => $low]),
                'href' => route('purchase-orders'),
                'count' => $low,
            ];
        }

        /* --- enquiries nobody has answered ------------------------------- */
        $newEnquiries = Enquiry::where('status', 'new')->count();

        if ($newEnquiries > 0) {
            $alerts[] = [
                'tone' => 'info',
                'icon' => 'inbox',
                'title' => __('Enquiries not yet answered'),
                'detail' => trans_choice('{1}1 waiting|[2,*]:count waiting', $newEnquiries, ['count' => $newEnquiries]),
                'href' => route('enquiries', ['status' => 'new']),
                'count' => $newEnquiries,
            ];
        }

        /* --- quotes sent and gone quiet ---------------------------------- */
        $stale = Quote::awaitingReply()
            ->where('sent_at', '<=', now()->subDays(3))
            ->count();

        if ($stale > 0) {
            $alerts[] = [
                'tone' => 'warn',
                'icon' => 'file-text',
                'title' => __('Quotes with no reply'),
                'detail' => trans_choice('{1}1 sent over 3 days ago|[2,*]:count sent over 3 days ago', $stale, ['count' => $stale]),
                'href' => route('quotes', ['status' => 'sent']),
                'count' => $stale,
            ];
        }

        /* --- deliveries that should have arrived ------------------------- */
        $latePo = PurchaseOrder::outstanding()
            ->whereNotNull('expected_on')
            ->whereDate('expected_on', '<', today())
            ->count();

        if ($latePo > 0) {
            $alerts[] = [
                'tone' => 'warn',
                'icon' => 'truck',
                'title' => __('Deliveries overdue'),
                'detail' => trans_choice('{1}1 order past its date|[2,*]:count orders past their date', $latePo, ['count' => $latePo]),
                'href' => route('purchase-orders'),
                'count' => $latePo,
            ];
        }

        /* --- prep that has slipped --------------------------------------- */
        $overdueTasks = OrderTask::outstanding()
            ->whereHas('order', fn ($q) => $q->whereDate('event_date', '>=', today()->subDays(1))
                ->whereNotIn('status', ['cancelled', 'completed']))
            ->get()
            ->filter(fn (OrderTask $t) => $t->isOverdue())
            ->count();

        if ($overdueTasks > 0) {
            $alerts[] = [
                'tone' => 'bad',
                'icon' => 'flame',
                'title' => __('Prep tasks overdue'),
                'detail' => trans_choice('{1}1 task past due|[2,*]:count tasks past due', $overdueTasks, ['count' => $overdueTasks]),
                'href' => route('prep'),
                'count' => $overdueTasks,
            ];
        }

        /* --- people still clocked in from a previous day ------------------ */
        $stillIn = AttendanceRecord::open()->whereDate('worked_on', '<', today())->count();

        if ($stillIn > 0) {
            $alerts[] = [
                'tone' => 'warn',
                'icon' => 'clock',
                'title' => __('Still clocked in from an earlier day'),
                'detail' => trans_choice('{1}1 shift left open|[2,*]:count shifts left open', $stillIn, ['count' => $stillIn]),
                'href' => route('attendance'),
                'count' => $stillIn,
            ];
        }

        /* --- kit that never came back ------------------------------------ */
        $kitOut = EquipmentAssignment::stillOut()
            ->whereHas('order', fn ($q) => $q->whereDate('event_date', '<', today()->subDay()))
            ->count();

        if ($kitOut > 0) {
            $alerts[] = [
                'tone' => 'warn',
                'icon' => 'stack',
                'title' => __('Equipment not booked back in'),
                'detail' => trans_choice('{1}1 load outstanding|[2,*]:count loads outstanding', $kitOut, ['count' => $kitOut]),
                'href' => route('equipment'),
                'count' => $kitOut,
            ];
        }

        return collect($alerts);
    }

    public function alertCount(): int
    {
        // Cheap enough to run per request, and always current — a stale badge
        // is worse than none.
        return $this->alerts()->count();
    }

    /**
     * Headline money for a period.
     *
     * @return array{revenue: int, collected: int, outstanding: int, expenses: int, food_cost: int, waste: int, profit: int}
     */
    public function financials(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $orders = Order::whereBetween('event_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->with('payments')
            ->get();

        $revenue = (int) $orders->sum('total_amount');
        $collected = (int) $orders->sum(fn (Order $o) => $o->paidAmount());

        $expenses = (int) Expense::between($from, $to)->sum('amount');

        $foodCost = (int) round(
            WasteLog::query()
                ->whereBetween('wasted_on', [$from, $to])
                ->get()
                ->sum(fn (WasteLog $w) => $w->cost()) * 100
        );

        return [
            'revenue' => $revenue,
            'collected' => $collected,
            'outstanding' => max(0, $revenue - $collected),
            'expenses' => $expenses,
            'food_cost' => 0,
            'waste' => $foodCost,
            'profit' => $revenue - $expenses - $foodCost,
        ];
    }
}
