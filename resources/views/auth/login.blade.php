@extends('auth.layout')

@section('title', __('Sign in'))

@section('card')
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="label-sm">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username webauthn" inputmode="email"
                   class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-sm outline-none transition-colors focus:border-royal">
        </div>

        <div>
            <div class="flex items-baseline justify-between gap-3">
                <label for="password" class="label-sm">{{ __('Password') }}</label>
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-link hover:underline">{{ __('Forgot password?') }}</a>
            </div>
            <input id="password" name="password" type="password"
                   required autocomplete="current-password"
                   class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-sm outline-none transition-colors focus:border-royal">
        </div>

        <label class="flex items-center gap-2.5 text-sm text-text-muted">
            <input type="checkbox" name="remember" data-passkey-remember class="size-4 rounded border-line-strong">
            {{ __('Keep me signed in') }}
        </label>

        <button type="submit"
                class="tap w-full rounded-lg bg-navy px-4 text-sm font-semibold text-cream transition-colors hover:bg-royal-deep">
            {{ __('Sign in') }}
        </button>
    </form>

    {{-- Shown by passkeys.js only in browsers that support passkeys --}}
    <div data-passkey-login-wrap class="hidden">
        <div class="my-5 flex items-center gap-3 text-xs text-text-faint">
            <span class="h-px flex-1 bg-line"></span>{{ __('or') }}<span class="h-px flex-1 bg-line"></span>
        </div>
        <button type="button" data-passkey-login
                data-options-url="{{ route('passkey.login-options') }}" data-verify-url="{{ route('passkey.login') }}"
                class="tap w-full rounded-lg border border-line-strong px-4 text-sm font-semibold text-text transition-colors hover:bg-surface-2">
            {{ __('Sign in with a passkey') }}
        </button>
    </div>
@endsection
