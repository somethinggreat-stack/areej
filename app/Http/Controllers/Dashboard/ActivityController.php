<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only by design. An audit trail somebody can edit is not an audit trail.
 */
class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::with('user')
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->integer('user')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')->toString()))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $q->where('summary', 'like', '%'.$request->string('q')->toString().'%');
            })
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('dashboard.activity.index', [
            'logs' => $logs,
            'team' => User::orderBy('name')->get(),
            'actions' => ActivityLog::query()
                ->select('action')
                ->distinct()
                ->pluck('action', 'action')
                ->all(),
        ]);
    }
}
