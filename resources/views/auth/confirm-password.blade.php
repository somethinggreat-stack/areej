@extends('auth.layout')

@section('title', __('Confirm your password'))

@section('card')
    <h2 class="text-base font-semibold text-text">{{ __('Confirm it is you') }}</h2>
    <p class="mt-1.5 text-sm text-text-muted">{{ __('Your account security settings need your password first. You will not be asked again for a few hours.') }}</p>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <label for="password" class="label-sm">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password"
                   class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-sm outline-none transition-colors focus:border-royal">
        </div>

        <button type="submit"
                class="tap w-full rounded-lg bg-navy px-4 text-sm font-semibold text-cream transition-colors hover:bg-royal-deep">
            {{ __('Continue') }}
        </button>
    </form>
@endsection

@section('below')
    <a href="{{ route('dashboard') }}" class="font-semibold text-cream/70 hover:text-cream">{{ __('Back to the dashboard') }}</a>
@endsection
