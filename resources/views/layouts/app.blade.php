@php
    $locale = app()->getLocale();
    $rtl = $locale === 'ur';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Dashboard')) — Midland Catering</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Theme is resolved before first paint so a dark-mode user never gets a
         white flash on the way in. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('mc-theme');
                var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.dataset.theme = theme;
            } catch (e) {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>

    @if ($rtl)
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=noto-nastaliq-urdu:400,600" rel="stylesheet">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/passkeys.js'])
    @livewireStyles
</head>
<body class="min-h-svh bg-app text-text">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-50 focus:rounded-lg focus:bg-gold focus:px-4 focus:py-2 focus:font-semibold focus:text-ink">
        {{ __('Skip to content') }}
    </a>

    <div class="flex min-h-svh flex-col lg:flex-row">
        @include('layouts.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('layouts.partials.topbar')

            <main id="main" class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @include('layouts.partials.flash')
                @yield('content')
            </main>

            <footer class="px-4 pb-6 text-center text-xs text-text-faint sm:px-6 lg:px-8">
                Midland Catering Ltd &middot; {{ __('Operations') }}
            </footer>
        </div>
    </div>

    {{-- Confirmation dialog. One native <dialog> reused by every destructive
         action, so no page has to ship its own modal. --}}
    <dialog id="confirm-dialog"
            class="max-w-sm rounded-xl border border-line bg-surface p-0 text-text backdrop:bg-ink/55 backdrop:backdrop-blur-sm">
        <form method="dialog" class="p-6">
            <h2 class="text-base font-semibold">{{ __('Are you sure?') }}</h2>
            <p id="confirm-message" class="mt-2 text-sm text-text-muted"></p>
            <div class="mt-6 flex justify-end gap-2">
                <button value="cancel" class="tap rounded-lg border border-line-strong px-4 text-sm font-semibold text-text transition-colors hover:bg-surface-2">
                    {{ __('Cancel') }}
                </button>
                <button value="confirm" class="tap rounded-lg bg-danger-solid px-4 text-sm font-semibold text-white transition-opacity hover:opacity-90">
                    {{ __('Yes, continue') }}
                </button>
            </div>
        </form>
    </dialog>

    @livewireScripts
</body>
</html>
