<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Sign in') }} — Midland Catering</title>
    @vite(['resources/css/app.css'])
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

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="label-sm">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username" inputmode="email"
                           class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-sm outline-none transition-colors focus:border-royal">
                </div>

                <div>
                    <label for="password" class="label-sm">{{ __('Password') }}</label>
                    <input id="password" name="password" type="password"
                           required autocomplete="current-password"
                           class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-sm outline-none transition-colors focus:border-royal">
                </div>

                <label class="flex items-center gap-2.5 text-sm text-text-muted">
                    <input type="checkbox" name="remember" class="size-4 rounded border-line-strong">
                    {{ __('Keep me signed in') }}
                </label>

                <button type="submit"
                        class="tap w-full rounded-lg bg-navy px-4 text-sm font-semibold text-cream transition-colors hover:bg-royal-deep">
                    {{ __('Sign in') }}
                </button>
            </form>
        </div>

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
