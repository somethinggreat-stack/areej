<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * The dashboard menu, and which parts of it the business actually uses.
 *
 * The client asked for only the sections they use. Rather than delete the
 * rest, the owner ticks what to show in Settings; a hidden section's pages
 * still work, they are just off the menu, so bringing one back is one tick.
 */
class Menu
{
    /**
     * Off the menu until the owner turns them on: the detailed systems the
     * simple order book replaced.
     *
     * @var list<string>
     */
    public const DEFAULT_HIDDEN = [
        'calendar', 'prep', 'stock-counts', 'purchase-orders', 'suppliers',
        'equipment', 'expenses', 'reports', 'activity',
    ];

    /**
     * Always on the menu, so nobody can hide their way out of Settings.
     *
     * @var list<string>
     */
    public const ALWAYS_SHOWN = ['dashboard', 'my-timesheet', 'settings'];

    /**
     * Grouped the way a catering day actually runs. Each entry carries the
     * role level that may see it, so nobody is shown a link that would 403.
     *
     * @return list<array{label: ?string, items: list<array{route: string, icon: string, label: string, level: int, when?: callable(User): bool}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'label' => null,
                'items' => [
                    ['route' => 'dashboard', 'icon' => 'grid', 'label' => __('Overview'), 'level' => 10],
                    ['route' => 'calendar', 'icon' => 'calendar', 'label' => __('Diary'), 'level' => 30],
                    ['route' => 'my-timesheet', 'icon' => 'clock', 'label' => __('My timesheet'), 'level' => 10, 'when' => fn (User $user) => $user->staffProfile !== null],
                ],
            ],
            [
                'label' => __('Sales'),
                'items' => [
                    ['route' => 'enquiries', 'icon' => 'inbox', 'label' => __('Enquiries'), 'level' => 30],
                    ['route' => 'quotes', 'icon' => 'file-text', 'label' => __('Quotes'), 'level' => 30, 'when' => fn (User $user) => $user->canHandleQuotes()],
                    ['route' => 'orders', 'icon' => 'clipboard', 'label' => __('Orders'), 'level' => 30],
                    ['route' => 'customers', 'icon' => 'users', 'label' => __('Customers'), 'level' => 30, 'when' => fn (User $user) => $user->canSeeFinancials()],
                    ['route' => 'payments', 'icon' => 'receipt', 'label' => __('Payments'), 'level' => 30, 'when' => fn (User $user) => $user->canSeeFinancials()],
                    ['route' => 'weekly-summary', 'icon' => 'chart', 'label' => __('Weekly summary'), 'level' => 30, 'when' => fn (User $user) => $user->canSeeFinancials()],
                ],
            ],
            [
                'label' => __('Kitchen'),
                'items' => [
                    ['route' => 'dishes', 'icon' => 'chef', 'label' => __('Menu & recipes'), 'level' => 50],
                    ['route' => 'prep', 'icon' => 'flame', 'label' => __('Prep board'), 'level' => 50],
                ],
            ],
            [
                'label' => __('Stock'),
                'items' => [
                    ['route' => 'inventory', 'icon' => 'box', 'label' => __('Inventory'), 'level' => 50],
                    ['route' => 'waste', 'icon' => 'trash', 'label' => __('Waste'), 'level' => 50],
                    ['route' => 'stock-counts', 'icon' => 'check', 'label' => __('Stock counts'), 'level' => 50],
                    ['route' => 'purchase-orders', 'icon' => 'cart', 'label' => __('Purchasing'), 'level' => 40],
                    ['route' => 'suppliers', 'icon' => 'truck', 'label' => __('Suppliers'), 'level' => 40],
                    ['route' => 'equipment', 'icon' => 'stack', 'label' => __('Equipment'), 'level' => 50],
                ],
            ],
            [
                'label' => __('People'),
                'items' => [
                    ['route' => 'staff', 'icon' => 'users', 'label' => __('Staff'), 'level' => 80],
                    ['route' => 'attendance', 'icon' => 'clock', 'label' => __('Attendance'), 'level' => 80],
                    ['route' => 'timesheets', 'icon' => 'wallet', 'label' => __('Pay run'), 'level' => 80],
                ],
            ],
            [
                'label' => __('Money'),
                'items' => [
                    ['route' => 'expenses', 'icon' => 'receipt', 'label' => __('Expenses'), 'level' => 60],
                    ['route' => 'reports', 'icon' => 'chart', 'label' => __('Reports'), 'level' => 60],
                ],
            ],
            [
                'label' => __('System'),
                'items' => [
                    ['route' => 'users', 'icon' => 'key', 'label' => __('Logins'), 'level' => 80],
                    ['route' => 'backup', 'icon' => 'download', 'label' => __('Backup'), 'level' => 80],
                    ['route' => 'activity', 'icon' => 'activity', 'label' => __('Activity'), 'level' => 80],
                    ['route' => 'settings', 'icon' => 'settings', 'label' => __('Settings'), 'level' => 100],
                ],
            ],
        ];
    }

    /**
     * Routes the owner has taken off the menu. Never set means the defaults.
     *
     * @return list<string>
     */
    public static function hidden(): array
    {
        $saved = Setting::get('menu_hidden');

        if ($saved === null) {
            return self::DEFAULT_HIDDEN;
        }

        return array_values(array_diff(array_filter(explode(',', (string) $saved)), self::ALWAYS_SHOWN));
    }

    /**
     * @param  list<string>  $routes
     */
    public static function hide(array $routes): void
    {
        $known = array_column(self::choices()->flatten(1)->all(), 'route');

        Setting::put('menu_hidden', implode(',', array_values(array_intersect($known, $routes))), 'string', 'menu');
    }

    /**
     * The menu this person sees: their level, their role's extras, and only
     * the sections the business uses.
     *
     * @return Collection<int, array{label: ?string, items: list<array<string, mixed>>}>
     */
    public static function for(?User $user): Collection
    {
        $hidden = self::hidden();

        return collect(self::groups())
            ->map(function (array $group) use ($user, $hidden): array {
                $group['items'] = array_values(array_filter(
                    $group['items'],
                    fn (array $item) => ($user?->hasRoleLevel($item['level']) ?? false)
                        && (! isset($item['when']) || $item['when']($user))
                        && ! in_array($item['route'], $hidden, true)
                        && Route::has($item['route'])
                ));

                return $group;
            })
            ->filter(fn (array $group) => count($group['items']) > 0)
            ->values();
    }

    /**
     * What Settings offers to show or hide, grouped as on the menu.
     *
     * @return Collection<string, list<array{route: string, label: string}>>
     */
    public static function choices(): Collection
    {
        return collect(self::groups())
            ->mapWithKeys(fn (array $group) => [
                $group['label'] ?? __('General') => array_values(array_map(
                    fn (array $item) => ['route' => $item['route'], 'label' => $item['label']],
                    array_filter($group['items'], fn (array $item) => ! in_array($item['route'], self::ALWAYS_SHOWN, true)),
                )),
            ])
            ->filter(fn (array $items) => $items !== []);
    }
}
