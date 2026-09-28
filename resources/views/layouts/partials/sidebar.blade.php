@php
    /*
     * Grouped the way a catering day actually runs, rather than one flat list
     * of eleven links. Each entry carries the role level that may see it, so
     * nobody is ever shown a link that would 403 on them.
     */
    $groups = [
        [
            'label' => null,
            'items' => [
                ['route' => 'dashboard', 'icon' => 'grid', 'label' => __('Overview'), 'level' => 10],
                ['route' => 'calendar', 'icon' => 'calendar', 'label' => __('Diary'), 'level' => 30],
            ],
        ],
        [
            'label' => __('Sales'),
            'items' => [
                ['route' => 'enquiries', 'icon' => 'inbox', 'label' => __('Enquiries'), 'level' => 30],
                ['route' => 'quotes', 'icon' => 'file-text', 'label' => __('Quotes'), 'level' => 30],
                ['route' => 'orders', 'icon' => 'clipboard', 'label' => __('Orders'), 'level' => 30],
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
                ['route' => 'stock-counts', 'icon' => 'check', 'label' => __('Stock counts'), 'level' => 50],
                ['route' => 'purchase-orders', 'icon' => 'cart', 'label' => __('Purchasing'), 'level' => 40],
                ['route' => 'suppliers', 'icon' => 'truck', 'label' => __('Suppliers'), 'level' => 40],
                ['route' => 'waste', 'icon' => 'trash', 'label' => __('Waste'), 'level' => 50],
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
                ['route' => 'activity', 'icon' => 'activity', 'label' => __('Activity'), 'level' => 80],
                ['route' => 'settings', 'icon' => 'settings', 'label' => __('Settings'), 'level' => 100],
            ],
        ],
    ];

    $user = auth()->user();

    $visible = collect($groups)
        ->map(function (array $group) use ($user): array {
            $group['items'] = array_values(array_filter(
                $group['items'],
                fn (array $item) => ($user?->hasRoleLevel($item['level']) ?? false) && \Illuminate\Support\Facades\Route::has($item['route'])
            ));

            return $group;
        })
        ->filter(fn (array $group) => count($group['items']) > 0)
        ->values();
@endphp

<aside class="shrink-0 bg-navy text-cream lg:flex lg:w-64 lg:flex-col">
    <div class="flex items-center justify-between gap-3 px-5 py-4 lg:py-6">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-gold text-sm font-bold text-ink">MC</span>
            <span class="min-w-0 leading-tight">
                <span class="block truncate text-sm font-semibold">Midland Catering</span>
                <span class="block text-[0.68rem] font-semibold tracking-[0.08em] text-cream/70 uppercase">{{ __('Operations') }}</span>
            </span>
        </a>

        <button type="button" data-nav-toggle aria-expanded="false" aria-controls="app-nav"
                class="tap -me-2 grid place-items-center rounded-lg text-cream/70 transition-colors hover:bg-white/10 hover:text-cream lg:hidden">
            <span class="sr-only">{{ __('Main navigation') }}</span>
            <x-icon name="grid" class="size-5" />
        </button>
    </div>

    <nav id="app-nav" data-nav class="hidden px-3 pb-4 lg:block lg:flex-1 lg:overflow-y-auto lg:pb-6" aria-label="{{ __('Main navigation') }}">
        @foreach ($visible as $group)
            @if ($group['label'])
                <p class="px-3 pt-4 pb-1.5 text-[0.62rem] font-bold tracking-[0.1em] text-cream/65 uppercase">{{ $group['label'] }}</p>
            @endif

            <ul class="space-y-0.5">
                @foreach ($group['items'] as $item)
                    @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}"
                           @if ($active) aria-current="page" @endif
                           class="tap flex items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors
                                  {{ $active ? 'bg-gold text-ink' : 'text-cream/70 hover:bg-white/10 hover:text-cream' }}">
                            <x-icon :name="$item['icon']" class="size-4 shrink-0" />
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>
</aside>
