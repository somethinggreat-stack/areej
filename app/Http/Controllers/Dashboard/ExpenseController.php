<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(private readonly Activity $activity) {}

    public function index(Request $request): View
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();
        $category = $request->string('category')->toString();

        $query = Expense::with(['order', 'supplier', 'recorder'])
            ->between($from, $to)
            ->when($category !== '', fn ($q) => $q->where('category', $category));

        $inRange = (clone $query)->get();

        return view('dashboard.expenses.index', [
            'expenses' => $query->latest('spent_on')->paginate(30)->withQueryString(),
            'from' => $from,
            'to' => $to,
            'category' => $category,
            'total' => (int) $inRange->sum('amount'),
            'byCategory' => $inRange->groupBy('category')
                ->map(fn ($group) => (int) $group->sum('amount'))
                ->sortDesc(),
            'orders' => Order::orderByDesc('event_date')->limit(60)->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:ingredients,packaging,fuel,vehicle,equipment_hire,venue,wages,utilities,rent,repairs,other'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'method' => ['required', 'in:cash,bank_transfer,card,cheque,other'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'reference' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['amount'] = (int) round((float) $data['amount'] * 100);
        $data['recorded_by'] = $request->user()->id;

        $expense = Expense::create($data);

        $this->activity->created($expense, __('Expense recorded: :what', ['what' => $expense->description]));

        return back()->with('status', __('Expense recorded.'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $what = $expense->description;
        $expense->delete();

        $this->activity->deleted($expense, __('Expense deleted: :what', ['what' => $what]));

        return back()->with('status', __('Expense removed.'));
    }
}
