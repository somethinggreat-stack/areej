<?php

namespace App\Services;

use App\Models\DishIngredient;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderDish;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns "300 guests, biryani and karahi" into a shopping list.
 *
 * This is the join between the kitchen and the store room, and the reason
 * recipes are held per hundred guests: multiply, sum by ingredient, compare
 * against the shelf, and whatever is short becomes a purchase order.
 */
class IngredientPlanner
{
    /**
     * What one order needs, ingredient by ingredient.
     *
     * @return Collection<int, array{
     *     item: InventoryItem,
     *     required: float,
     *     on_hand: float,
     *     shortfall: float,
     *     cost: float,
     *     dishes: array<int, string>
     * }>
     */
    public function requirementsFor(Order $order): Collection
    {
        $orderDishes = $order->relationLoaded('dishes')
            ? $order->dishes
            : $order->dishes()->with('dish.ingredients.inventoryItem')->get();

        $rows = [];

        foreach ($orderDishes as $orderDish) {
            /** @var OrderDish $orderDish */
            foreach ($orderDish->dish?->ingredients ?? [] as $ingredient) {
                /** @var DishIngredient $ingredient */
                $item = $ingredient->inventoryItem;

                if ($item === null) {
                    continue;
                }

                $rows[$item->id] ??= [
                    'item' => $item,
                    'required' => 0.0,
                    'on_hand' => (float) $item->current_quantity,
                    'shortfall' => 0.0,
                    'cost' => 0.0,
                    'dishes' => [],
                ];

                $rows[$item->id]['required'] += $ingredient->quantityFor($orderDish->guests);
                $rows[$item->id]['dishes'][] = $orderDish->dish->displayName();
            }
        }

        return collect($rows)
            ->map(function (array $row): array {
                $row['required'] = round($row['required'], 3);
                $row['shortfall'] = round(max(0, $row['required'] - $row['on_hand']), 3);
                $row['cost'] = round($row['required'] * (float) ($row['item']->unit_cost ?? 0), 2);
                $row['dishes'] = array_values(array_unique($row['dishes']));

                return $row;
            })
            ->sortByDesc('shortfall')
            ->values();
    }

    /**
     * Combined requirements across several orders — what the week needs, so one
     * shop covers Saturday and Sunday rather than two panicked trips.
     *
     * @param  Collection<int, Order>  $orders
     * @return Collection<int, array<string, mixed>>
     */
    public function requirementsForMany(Collection $orders): Collection
    {
        $merged = [];

        foreach ($orders as $order) {
            foreach ($this->requirementsFor($order) as $row) {
                $id = $row['item']->id;

                $merged[$id] ??= [
                    'item' => $row['item'],
                    'required' => 0.0,
                    'on_hand' => $row['on_hand'],
                    'shortfall' => 0.0,
                    'cost' => 0.0,
                    'dishes' => [],
                ];

                $merged[$id]['required'] += $row['required'];
                $merged[$id]['dishes'] = array_merge($merged[$id]['dishes'], $row['dishes']);
            }
        }

        return collect($merged)
            ->map(function (array $row): array {
                $row['required'] = round($row['required'], 3);
                $row['shortfall'] = round(max(0, $row['required'] - $row['on_hand']), 3);
                $row['cost'] = round($row['required'] * (float) ($row['item']->unit_cost ?? 0), 2);
                $row['dishes'] = array_values(array_unique($row['dishes']));

                return $row;
            })
            ->sortByDesc('shortfall')
            ->values();
    }

    /**
     * Estimated ingredient cost of an order, in pence. Null when none of the
     * chosen dishes have a recipe yet — a zero would read as "free".
     */
    public function estimatedFoodCost(Order $order): ?int
    {
        $requirements = $this->requirementsFor($order);

        if ($requirements->isEmpty()) {
            return null;
        }

        return (int) round($requirements->sum('cost') * 100);
    }

    /**
     * Takes the planned ingredients off the shelf once a job has been cooked.
     *
     * Guarded so a job cannot be deducted twice, and refuses rather than
     * driving any line negative — a shortfall means somebody needs to shop, not
     * that the system should invent stock it does not have.
     *
     * @return array{deducted: int, blocked: array<int, string>}
     */
    public function deduct(Order $order, ?User $by = null): array
    {
        $pending = $order->dishes()->where('ingredients_deducted', false)->with('dish.ingredients.inventoryItem')->get();

        if ($pending->isEmpty()) {
            return ['deducted' => 0, 'blocked' => []];
        }

        $requirements = $this->requirementsFor($order);
        $blocked = $requirements
            ->filter(fn (array $row) => $row['shortfall'] > 0)
            ->map(fn (array $row) => $row['item']->displayName())
            ->values()
            ->all();

        if ($blocked !== []) {
            return ['deducted' => 0, 'blocked' => $blocked];
        }

        $ledger = app(StockLedger::class);
        $count = 0;

        DB::transaction(function () use ($requirements, $order, $by, $ledger, &$count): void {
            foreach ($requirements as $row) {
                if ($row['required'] <= 0) {
                    continue;
                }

                $ledger->record(
                    $row['item'],
                    StockMovement::TYPE_EVENT_USAGE,
                    -1 * $row['required'],
                    $by,
                    $order,
                    $row['item']->unit_cost,
                    __('Used on :ref', ['ref' => $order->reference]),
                );

                $count++;
            }

            $order->dishes()->update(['ingredients_deducted' => true]);
        });

        return ['deducted' => $count, 'blocked' => []];
    }
}
