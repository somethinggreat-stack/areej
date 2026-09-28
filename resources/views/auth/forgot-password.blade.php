@extends('auth.layout')

@section('title', __('Forgot password'))

@section('card')
    <h2 class="text-base font-semibold text-text">{{ __('Forgot your password?') }}</h2>
    <p class="mt-1.5 text-sm text-text-muted">{{ __('Enter your email and we will send you a link to choose a new one. If no email arrives, ask the owner or management to reset it for you.') }}</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="email" class="label-sm">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username" inputmode="email"
                   class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-sm outline-none transition-colors focus:border-royal">
        </div>

        <button type="submit"
                class="tap w-full rounded-lg bg-navy px-4 text-sm font-semibold text-cream transition-colors hover:bg-royal-deep">
            {{ __('Email me a reset link') }}
        </button>
    </form>
@endsection

@section('below')
    <a href="{{ route('login') }}" class="font-semibold text-cream/70 hover:text-cream">{{ __('Back to sign in') }}</a>
@endsection
