<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A dish as an operational unit: what it sells for, how big a portion is, and
 * what it is made of. The recipe is what turns a guest count into a shopping
 * list.
 */
class Dish extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name_en', 'name_ur', 'course', 'description', 'price_per_head',
        'portion_grams', 'is_vegetarian', 'contains_dairy', 'contains_nuts',
        'is_signature', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_per_head' => 'integer',
            'portion_grams' => 'integer',
            'is_vegetarian' => 'boolean',
            'contains_dairy' => 'boolean',
            'contains_nuts' => 'boolean',
            'is_signature' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<DishIngredient, $this> */
    public function ingredients(): HasMany
    {
        return $this->hasMany(DishIngredient::class);
    }

    /** @return HasMany<OrderDish, $this> */
    public function orderDishes(): HasMany
    {
        return $this->hasMany(OrderDish::class);
    }

    /** @param  Builder<Dish>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * A menu runs appetisers to drinks. Ordering by the course column sorts it
     * alphabetically instead — desserts before mains — which reads as a mistake
     * on every dropdown and printed quote.
     *
     * @param  Builder<Dish>  $query
     */
    public function scopeInMenuOrder(Builder $query): void
    {
        // A CASE expression rather than MySQL's FIELD(): the same ordering has
        // to work on SQLite, which is what the test suite runs against.
        $cases = [];
        $bindings = [];

        foreach (array_keys(self::courseLabels()) as $position => $course) {
            $cases[] = 'WHEN ? THEN '.$position;
            $bindings[] = $course;
        }

        $query->orderByRaw(
            'CASE course '.implode(' ', $cases).' ELSE 99 END',
            $bindings
        )->orderBy('sort_order')->orderBy('name_en');
    }

    /** Position of this dish's course in the menu, for grouping in views. */
    public function courseRank(): int
    {
        return array_search($this->course, array_keys(self::courseLabels()), true) ?: 0;
    }

    public function displayName(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ur'
            ? ($this->name_ur ?: $this->name_en)
            : $this->name_en;
    }

    public function priceInPounds(): float
    {
        return $this->price_per_head / 100;
    }

    public function hasRecipe(): bool
    {
        return $this->ingredients()->exists();
    }

    /**
     * Ingredient cost of feeding one head, in pence. Null when no recipe has
     * been entered — which is different from "it costs nothing".
     */
    public function ingredientCostPerHead(): ?int
    {
        $ingredients = $this->relationLoaded('ingredients')
            ? $this->ingredients
            : $this->ingredients()->with('inventoryItem')->get();

        if ($ingredients->isEmpty()) {
            return null;
        }

        $per100 = $ingredients->sum(
            fn (DishIngredient $row) => (float) $row->quantity_per_100 * (float) ($row->inventoryItem?->unit_cost ?? 0)
        );

        return (int) round($per100 * 100 / 100);
    }

    /** Gross margin per head as a percentage, or null when it cannot be known. */
    public function marginPercent(): ?float
    {
        $cost = $this->ingredientCostPerHead();

        if ($cost === null || $this->price_per_head <= 0) {
            return null;
        }

        return round(($this->price_per_head - $cost) / $this->price_per_head * 100, 1);
    }

    /** @return array<string, string> */
    public static function courseLabels(): array
    {
        return [
            'appetisers' => __('Appetisers'),
            'starters' => __('Starters'),
            'mains' => __('Mains'),
            'sides' => __('Rice & Breads'),
            'desserts' => __('Desserts'),
            'drinks' => __('Hot Drinks'),
        ];
    }

    public function courseLabel(): string
    {
        return self::courseLabels()[$this->course] ?? $this->course;
    }
}
