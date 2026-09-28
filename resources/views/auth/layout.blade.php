<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — Midland Catering</title>
    @vite(['resources/css/app.css', 'resources/js/passkeys.js'])
</head>
<body class="grid min-h-svh place-items-center bg-navy px-4 py-10">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-gold text-lg font-bold text-ink">MC</span>
            <h1 class="mt-5 text-xl font-semibold text-cream">Midland Catering</h1>
            <p class="label-sm mt-1 !text-cream/45">{{ __('Operations Dashboard') }}</p>
        </div>

        <div class="card p-6">
            @if ($errors->any())
                <div role="alert" class="mb-5 rounded-lg border border-bad/30 bg-bad-bg px-4 py-3 text-sm text-bad">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div role="status" class="mb-5 rounded-lg border border-good/30 bg-good-bg px-4 py-3 text-sm text-good">
                    {{ session('status') }}
                </div>
            @endif

            <div data-passkey-error role="alert" class="mb-5 hidden rounded-lg border border-bad/30 bg-bad-bg px-4 py-3 text-sm text-bad"></div>

            @yield('card')
        </div>

        @hasSection('below')
            <div class="mt-5 text-center text-sm">@yield('below')</div>
        @endif

        <div class="mt-6 flex justify-center gap-2">
            @foreach (['en' => 'English', 'ur' => 'اردو'] as $code => $label)
                <a href="{{ route('locale.switch', $code) }}"
                   class="rounded-md px-3 py-1.5 text-xs font-semibold transition-colors
                          {{ app()->getLocale() === $code ? 'bg-cream/15 text-cream' : 'text-cream/50 hover:text-cream' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
</body>
</html>
