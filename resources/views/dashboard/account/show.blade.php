@extends('layouts.app')

@section('title', __('Account'))


@section('content')
    <x-page-head :title="__('Your account')" :subtitle="$user->name.' · '.($user->role?->name_en ?? __('No role')).' · '.$user->email" />

    <div data-passkey-error role="alert" class="mb-5 hidden rounded-xl border border-bad/30 bg-bad-bg px-4 py-3 text-sm font-medium text-bad"></div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Password --}}
        <section class="card p-5">
            <h2 class="text-sm font-semibold text-text">{{ __('Change your password') }}</h2>
            <p class="mt-1 text-sm text-text-muted">{{ __('At least 12 characters. A short sentence is easier to remember than random letters.') }}</p>

            <form method="POST" action="{{ route('user-password.update') }}" class="mt-4 space-y-3">
                @csrf
                @method('PUT')

                @foreach ([
                    'current_password' => [__('Current password'), 'current-password'],
                    'password' => [__('New password'), 'new-password'],
                    'password_confirmation' => [__('Type the new password again'), 'new-password'],
                ] as $field => [$label, $autocomplete])
                    <div>
                        <label for="{{ $field }}" class="mb-1.5 block text-xs font-semibold text-text-muted">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="password" required autocomplete="{{ $autocomplete }}"
                               class="tap w-full rounded-lg border bg-surface px-3 py-2 text-sm text-text outline-none focus:border-royal-lit focus:ring-2 focus:ring-royal-lit/25 {{ $errors->updatePassword->has($field) ? 'border-bad' : 'border-line-strong' }}">
                        @if ($errors->updatePassword->has($field))
                            <p class="mt-1 text-xs font-medium text-bad">{{ $errors->updatePassword->first($field) }}</p>
                        @endif
                    </div>
                @endforeach

                <x-btn type="submit">{{ __('Change password') }}</x-btn>
            </form>
        </section>

        {{-- Two-step sign in --}}
        <section class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-text">{{ __('Two-step sign in') }}</h2>
                    <p class="mt-1 text-sm text-text-muted">{{ __('After your password, sign-in also asks for a 6-digit code from an app on your phone (Google Authenticator, Microsoft Authenticator or similar).') }}</p>
                </div>
                <x-badge :tone="$twoFactorOn ? 'good' : 'neutral'">{{ $twoFactorOn ? __('On') : __('Off') }}</x-badge>
            </div>

            @if ($twoFactorPending)
                <div class="mt-4 rounded-lg border border-line p-4">
                    <p class="text-sm font-medium text-text">{{ __('1. Scan this with your authenticator app') }}</p>
                    <div class="mt-3 inline-block rounded-lg bg-white p-3">{!! $qrCode !!}</div>
                    <p class="mt-2 text-xs text-text-muted">{{ __('Or type this key into the app:') }} <code class="font-mono text-text">{{ $setupKey }}</code></p>

                    <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-4 flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <label for="code" class="mb-1.5 block text-xs font-semibold text-text-muted">{{ __('2. Enter the 6-digit code it shows') }}</label>
                            <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required
                                   class="tap w-40 rounded-lg border border-line-strong bg-surface px-3 py-2 text-center tracking-[0.3em] text-text outline-none focus:border-royal-lit">
                            @if ($errors->confirmTwoFactorAuthentication->has('code'))
                                <p class="mt-1 text-xs font-medium text-bad">{{ $errors->confirmTwoFactorAuthentication->first('code') }}</p>
                            @endif
                        </div>
                        <x-btn type="submit">{{ __('Switch it on') }}</x-btn>
                    </form>
                </div>
            @elseif ($twoFactorOn)
                @if ($recoveryCodes !== [])
                    <div class="mt-4 rounded-lg border border-warn/35 bg-warn-bg p-4">
                        <p class="text-sm font-medium text-warn">{{ __('Recovery codes — each works once if you lose your phone. Write them down or print this page now.') }}</p>
                        <ul class="mt-3 grid grid-cols-2 gap-1.5 font-mono text-sm text-text">
                            @foreach ($recoveryCodes as $code)
                                <li>{{ $code }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                        @csrf
                        <x-btn type="submit" variant="secondary" size="sm">{{ __('New recovery codes') }}</x-btn>
                    </form>
                    <form method="POST" action="{{ route('two-factor.disable') }}">
                        @csrf
                        @method('DELETE')
                        <x-btn type="submit" variant="danger" size="sm" :confirm="__('Switch off two-step sign in?')">{{ __('Switch off') }}</x-btn>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-4">
                    @csrf
                    <x-btn type="submit">{{ __('Set up two-step sign in') }}</x-btn>
                </form>
            @endif
        </section>

        {{-- Passkeys --}}
        <section class="card p-5 lg:col-span-2">
            <h2 class="text-sm font-semibold text-text">{{ __('Passkeys') }}</h2>
            <p class="mt-1 text-sm text-text-muted">{{ __('Sign in with Face ID, a fingerprint or your phone\'s screen lock instead of typing a password. Add one on each phone or computer you use.') }}</p>

            @if ($passkeys->isNotEmpty())
                <ul class="mt-4 divide-y divide-line rounded-lg border border-line">
                    @foreach ($passkeys as $passkey)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-text">{{ $passkey->name }}</span>
                                <span class="block text-xs text-text-muted">
                                    {{ __('Added :date', ['date' => $passkey->created_at->format('j M Y')]) }}
                                    @if ($passkey->last_used_at)
                                        · {{ __('last used :when', ['when' => $passkey->last_used_at->diffForHumans()]) }}
                                    @endif
                                </span>
                            </span>
                            <form method="POST" action="{{ route('passkey.destroy', $passkey) }}">
                                @csrf
                                @method('DELETE')
                                <x-btn type="submit" variant="ghost" size="sm" :confirm="__('Remove this passkey?')">{{ __('Remove') }}</x-btn>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form data-passkey-register action="{{ route('passkey.store') }}" data-options-url="{{ route('passkey.registration-options') }}"
                  class="mt-4 flex flex-wrap items-end gap-3">
                <div class="min-w-[14rem] flex-1 sm:flex-none">
                    <label for="passkey-name" class="mb-1.5 block text-xs font-semibold text-text-muted">{{ __('Name this device') }}</label>
                    <input id="passkey-name" name="name" maxlength="60" placeholder="{{ __('e.g. My iPhone') }}"
                           class="tap w-full rounded-lg border border-line-strong bg-surface px-3 py-2 text-sm text-text outline-none focus:border-royal-lit">
                </div>
                <x-btn type="submit" icon="plus">{{ __('Add a passkey') }}</x-btn>
                <p data-passkey-unsupported class="hidden w-full text-xs text-text-faint">{{ __('This browser cannot create passkeys. Try Chrome, Safari or Edge on an up-to-date device.') }}</p>
            </form>
        </section>
    </div>
@endsection
