<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\DishIngredient;
use App\Models\InventoryItem;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The menu as an operational record, and the recipes behind it.
 *
 * Entering a recipe is the single highest-value thing anyone can do in this
 * system: it is what lets a guest count become a shopping list, a food cost and
 * a margin. Every screen here is built to make that entry quick.
 */
class DishController extends Controller
{
    public function __construct(private readonly Activity $activity) {}

    public function index(Request $request): View
    {
        $course = $request->string('course')->toString();

        $dishes = Dish::query()
            ->withCount('ingredients')
            ->with('ingredients.inventoryItem')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($q) => $q->where('name_en', 'like', $term)->orWhere('name_ur', 'like', $term));
            })
            ->when($course !== '', fn ($q) => $q->where('course', $course))
            ->when($request->string('filter')->toString() === 'no-recipe', fn ($q) => $q->doesntHave('ingredients'))
            ->when($request->string('filter')->toString() !== 'archived', fn ($q) => $q->where('is_active', true))
            ->when($request->string('filter')->toString() === 'archived', fn ($q) => $q->where('is_active', false))
            ->inMenuOrder()
            ->paginate(24)
            ->withQueryString();

        return view('dashboard.dishes.index', [
            'dishes' => $dishes,
            'course' => $course,
            'filter' => $request->string('filter')->toString(),
            'withoutRecipe' => Dish::active()->doesntHave('ingredients')->count(),
            'total' => Dish::active()->count(),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.dishes.form', [
            'dish' => new Dish(['course' => 'mains', 'portion_grams' => 250, 'is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dish = Dish::create($this->validated($request));

        $this->activity->created($dish, __('Dish added: :name', ['name' => $dish->name_en]));

        return redirect()->route('dishes.show', $dish)
            ->with('status', __('Added. Now enter what goes into it, per 100 guests.'));
    }

    public function show(Dish $dish): View
    {
        return view('dashboard.dishes.show', [
            'dish' => $dish->load('ingredients.inventoryItem.category'),
            'items' => InventoryItem::where('is_active', true)->orderBy('name_en')->get(),
        ]);
    }

    public function update(Request $request, Dish $dish): RedirectResponse
    {
        $before = $dish->getOriginal();
        $dish->update($this->validated($request));

        $this->activity->updated($dish, __('Dish updated: :name', ['name' => $dish->name_en]), $before);

        return back()->with('status', __('Saved.'));
    }

    public function destroy(Dish $dish): RedirectResponse
    {
        // A dish on a future job is still needed by the kitchen.
        $booked = $dish->orderDishes()
            ->whereHas('order', fn ($q) => $q->whereDate('event_date', '>=', today())->whereNot('status', 'cancelled'))
            ->count();

        if ($booked > 0) {
            return back()->withErrors(['form' => trans_choice(
                '{1}This dish is on 1 upcoming job. Archive it instead of deleting.|[2,*]This dish is on :count upcoming jobs. Archive it instead of deleting.',
                $booked,
                ['count' => $booked],
            )]);
        }

        $name = $dish->name_en;
        $dish->delete();

        $this->activity->deleted($dish, __('Dish deleted: :name', ['name' => $name]));

        return redirect()->route('dishes')->with('status', __('Deleted :name.', ['name' => $name]));
    }

    public function storeIngredient(Request $request, Dish $dish): RedirectResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'quantity_per_100' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Adding the same ingredient twice should correct the figure, not
        // create a duplicate line that silently doubles the forecast.
        $dish->ingredients()->updateOrCreate(
            ['inventory_item_id' => $data['inventory_item_id']],
            ['quantity_per_100' => $data['quantity_per_100'], 'notes' => $data['notes'] ?? null],
        );

        return back()->with('status', __('Recipe updated.'));
    }

    public function destroyIngredient(DishIngredient $ingredient): RedirectResponse
    {
        $ingredient->delete();

        return back()->with('status', __('Ingredient removed.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'course' => ['required', 'in:appetisers,starters,mains,sides,desserts,drinks'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_per_head' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'portion_grams' => ['required', 'integer', 'min:10', 'max:5000'],
            'is_vegetarian' => ['boolean'],
            'contains_dairy' => ['boolean'],
            'contains_nuts' => ['boolean'],
            'is_signature' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        // The price box is only on the form for management and the owner; for
        // anyone else it is left out, so saving a dish never wipes its price.
        if ($request->user()->canSeeFinancials()) {
            $data['price_per_head'] = (int) round((float) ($data['price_per_head'] ?? 0) * 100);
        } else {
            unset($data['price_per_head']);
        }

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
