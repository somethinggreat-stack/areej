@extends('auth.layout')

@section('title', __('Choose a new password'))

@section('card')
    <h2 class="text-base font-semibold text-text">{{ __('Choose a new password') }}</h2>

    <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="label-sm">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}"
                   required autocomplete="username" inputmode="email"
                   class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-sm outline-none transition-colors focus:border-royal">
        </div>

        <div>
            <label for="password" class="label-sm">{{ __('New password') }}</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="new-password"
                   class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-sm outline-none transition-colors focus:border-royal">
            <p class="mt-1.5 text-xs text-text-faint">{{ __('At least 12 characters.') }}</p>
        </div>

        <div>
            <label for="password_confirmation" class="label-sm">{{ __('Type it again') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="tap mt-1.5 w-full rounded-lg border border-line-strong px-3 py-2.5 text-sm outline-none transition-colors focus:border-royal">
        </div>

        <button type="submit"
                class="tap w-full rounded-lg bg-navy px-4 text-sm font-semibold text-cream transition-colors hover:bg-royal-deep">
            {{ __('Save and sign in') }}
        </button>
    </form>
@endsection
