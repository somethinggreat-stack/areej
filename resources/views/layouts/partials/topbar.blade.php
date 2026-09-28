@php
    $user = auth()->user();
    $alerts = app(\App\Services\OperationsFeed::class)->alertCount();
@endphp

<header class="sticky top-0 z-30 border-b border-line bg-surface/90 backdrop-blur">
    <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-semibold text-text">@yield('title', __('Overview'))</h1>
            @hasSection('subtitle')
                <p class="truncate text-sm text-text-muted">@yield('subtitle')</p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            @if ($alerts > 0)
                <a href="{{ route('dashboard') }}#alerts"
                   class="tap relative grid place-items-center rounded-lg border border-line px-2.5 text-text-muted transition-colors hover:bg-surface-2 hover:text-text"
                   title="{{ trans_choice('{1}1 thing needs attention|[2,*]:count things need attention', $alerts, ['count' => $alerts]) }}">
                    <x-icon name="bell" class="size-4" />
                    <span class="absolute -end-1 -top-1 grid size-4 place-items-center rounded-full bg-danger-solid text-[0.6rem] font-bold text-white tabular-nums">{{ min($alerts, 9) }}</span>
                    <span class="sr-only">{{ trans_choice('{1}1 thing needs attention|[2,*]:count things need attention', $alerts, ['count' => $alerts]) }}</span>
                </a>
            @endif

            <button type="button" data-theme-toggle
                    class="tap grid place-items-center rounded-lg border border-line px-2.5 text-text-muted transition-colors hover:bg-surface-2 hover:text-text">
                <x-icon name="sun" class="size-4 hidden dark-hide" data-theme-icon="light" />
                <x-icon name="moon" class="size-4" data-theme-icon="dark" />
                <span class="sr-only">{{ __('Switch theme') }}</span>
            </button>

            {{-- Urdu is the client's preferred language; English is kept alongside --}}
            <div class="flex overflow-hidden rounded-lg border border-line">
                @foreach (['en' => 'EN', 'ur' => 'اردو'] as $code => $label)
                    <a href="{{ route('locale.switch', $code) }}"
                       class="tap flex items-center px-2.5 text-xs font-semibold transition-colors
                              {{ app()->getLocale() === $code ? 'bg-navy text-cream' : 'text-text-muted hover:bg-surface-2' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <details class="relative">
                <summary class="tap flex cursor-pointer list-none items-center gap-2 rounded-lg border border-line px-2.5 text-xs font-semibold text-text-muted transition-colors hover:bg-surface-2">
                    <span class="grid size-6 shrink-0 place-items-center rounded-full bg-navy text-[0.6rem] font-bold text-cream">
                        {{ \Illuminate\Support\Str::of($user?->name ?? '?')->explode(' ')->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('') }}
                    </span>
                    <span class="hidden sm:inline">{{ \Illuminate\Support\Str::limit($user?->name, 14) }}</span>
                </summary>

                <div class="absolute end-0 z-40 mt-2 w-56 rounded-xl border border-line bg-surface p-1.5 shadow-lg">
                    <p class="px-3 py-2">
                        <span class="block truncate text-sm font-semibold text-text">{{ $user?->name }}</span>
                        <span class="block truncate text-xs text-text-muted">{{ $user?->role?->name_en ?? __('No role') }}</span>
                    </p>
                    <form method="POST" action="{{ route('logout') }}" class="border-t border-line pt-1.5">
                        @csrf
                        <button type="submit" class="tap w-full rounded-lg px-3 text-start text-sm font-medium text-text transition-colors hover:bg-surface-2">
                            {{ __('Sign out') }}
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>
