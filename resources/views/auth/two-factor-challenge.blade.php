@extends('auth.layout')

@section('title', __('Two-step sign in'))

@section('card')
    <h2 class="text-base font-semibold text-text">{{ __('Enter your 6-digit code') }}</h2>
    <p class="mt-1.5 text-sm text-text-muted">{{ __('Open your authenticator app and type the code shown for Midland Catering.') }}</p>

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="code" class="label-sm">{{ __('Code') }}</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9 ]*" maxlength="7"
                   autofocus autocomplete="one-time-code"
                   class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-center text-lg tracking-[0.3em] outline-none transition-colors focus:border-royal">
        </div>

        <button type="submit"
                class="tap w-full rounded-lg bg-navy px-4 text-sm font-semibold text-cream transition-colors hover:bg-royal-deep">
            {{ __('Sign in') }}
        </button>

        <details class="text-sm text-text-muted">
            <summary class="cursor-pointer font-semibold text-link">{{ __('Lost your phone? Use a recovery code') }}</summary>
            <div class="mt-3">
                <label for="recovery_code" class="label-sm">{{ __('Recovery code') }}</label>
                <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code"
                       class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-sm outline-none transition-colors focus:border-royal">
            </div>
        </details>
    </form>
@endsection

@section('below')
    <a href="{{ route('login') }}" class="font-semibold text-cream/70 hover:text-cream">{{ __('Back to sign in') }}</a>
@endsection
