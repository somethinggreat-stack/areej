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
                ['route' => 'my-timesheet', 'icon' => 'clock', 'label' => __('My timesheet'), 'level' => 10, 'when' => fn ($user) => $user->staffProfile !== null],
            ],
        ],
        [
            'label' => __('Sales'),
            'items' => [
                ['route' => 'enquiries', 'icon' => 'inbox', 'label' => __('Enquiries'), 'level' => 30],
                ['route' => 'quotes', 'icon' => 'file-text', 'label' => __('Quotes'), 'level' => 30, 'when' => fn ($user) => $user->canHandleQuotes()],
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
                ['route' => 'purchase-orders', 'icon' => 'cart', 'label' => __('Purchasing'), 'level' => 40, 'advanced' => true],
                ['route' => 'suppliers', 'icon' => 'truck', 'label' => __('Suppliers'), 'level' => 40, 'advanced' => true],
                ['route' => 'waste', 'icon' => 'trash', 'label' => __('Waste'), 'level' => 50, 'advanced' => true],
                ['route' => 'equipment', 'icon' => 'stack', 'label' => __('Equipment'), 'level' => 50, 'advanced' => true],
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
                ['route' => 'activity', 'icon' => 'activity', 'label' => __('Activity'), 'level' => 80, 'advanced' => true],
                ['route' => 'settings', 'icon' => 'settings', 'label' => __('Settings'), 'level' => 100],
            ],
        ],
    ];

    $user = auth()->user();
    // Settings → Simple menu hides the areas most days never need. The pages
    // themselves stay reachable; only the menu gets shorter.
    $simpleMenu = (bool) \App\Models\Setting::get('simple_menu', true);

    $visible = collect($groups)
        ->map(function (array $group) use ($user, $simpleMenu): array {
            $group['items'] = array_values(array_filter(
                $group['items'],
                fn (array $item) => ($user?->hasRoleLevel($item['level']) ?? false)
                    && (! isset($item['when']) || $item['when']($user))
                    && ! (($item['advanced'] ?? false) && $simpleMenu)
                    && \Illuminate\Support\Facades\Route::has($item['route'])
            ));

            return $group;
        })
        ->filter(fn (array $group) => count($group['items']) > 0)
        ->values();
@endphp

{{-- On desktop the sidebar is pinned at full viewport height, and links and headings
     scale with vh so the owner's full list (20 links) fits one screen down to ~620px
     tall. overflow-y-auto stays only as a fallback for anything shorter. --}}
<aside class="shrink-0 bg-navy text-cream lg:sticky lg:top-0 lg:flex lg:h-svh lg:w-64 lg:flex-col">
    <div class="flex shrink-0 items-center justify-between gap-3 px-5 py-4 lg:py-[clamp(0.6rem,1.8vh,1.25rem)]">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-gold lg:size-9 text-sm font-bold text-ink">MC</span>
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

    <nav id="app-nav" data-nav class="hidden px-3 pb-4 lg:block lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:pb-3" aria-label="{{ __('Main navigation') }}">
        @foreach ($visible as $group)
            @if ($group['label'])
                <p class="px-3 pt-4 pb-1.5 text-[0.62rem] font-bold lg:pt-[clamp(0.35rem,1.1vh,0.9rem)] lg:pb-1 lg:text-[0.58rem] lg:leading-none tracking-[0.1em] text-cream/65 uppercase">{{ $group['label'] }}</p>
            @endif

            <ul class="space-y-0.5">
                @foreach ($group['items'] as $item)
                    @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}"
                           @if ($active) aria-current="page" @endif
                           class="tap flex items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors lg:h-[clamp(1.25rem,3vh,2.25rem)] lg:min-h-0 lg:text-[0.8rem]
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
