<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The prep board: every outstanding task across every live job, in the order
 * they fall due. This is the screen a kitchen leaves open all day.
 */
class PrepController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->integer('days', 3);

        $orders = Order::whereBetween('event_date', [today(), today()->addDays($days)])
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['tasks.assignee', 'dishes.dish'])
            ->orderBy('event_date')
            ->orderByRaw('serve_time IS NULL, serve_time')
            ->get();

        $tasks = $orders
            ->flatMap(fn (Order $order) => $order->tasks)
            ->sortBy(fn (OrderTask $task) => $task->dueAt()?->timestamp ?? PHP_INT_MAX);

        return view('dashboard.prep.index', [
            'orders' => $orders,
            'days' => $days,
            'outstanding' => $tasks->where('is_done', false)->count(),
            'overdue' => $tasks->filter(fn (OrderTask $t) => $t->isOverdue())->count(),
            'done' => $tasks->where('is_done', true)->count(),
        ]);
    }

    public function toggle(Request $request, OrderTask $task): RedirectResponse
    {
        $done = ! $task->is_done;

        $task->update([
            'is_done' => $done,
            'done_at' => $done ? now() : null,
            'done_by' => $done ? $request->user()->id : null,
        ]);

        return back()->with('status', $done
            ? __('":task" ticked off.', ['task' => $task->title])
            : __('":task" put back on the list.', ['task' => $task->title]));
    }

    public function destroy(OrderTask $task): RedirectResponse
    {
        $title = $task->title;
        $task->delete();

        return back()->with('status', __('Removed ":task".', ['task' => $title]));
    }
}
