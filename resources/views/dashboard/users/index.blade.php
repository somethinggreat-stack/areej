@extends('layouts.app')

@section('title', __('Logins'))

@section('content')
    <x-page-head :title="__('Logins')" :subtitle="__('Who can sign in to the dashboard, and as what')" />

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <div class="min-w-0">
            <x-tabs param="filter" :current="$filter" :tabs="['' => __('Can sign in'), 'off' => __('Switched off')]" />

            @if ($users->isEmpty())
                <x-empty icon="key" :title="__('No logins here')" />
            @else
                <div class="space-y-3">
                    @foreach ($users as $person)
                        @php $editable = $actor->canManageLogin($person); @endphp
                        <details class="card overflow-hidden" @if ($errors->any() && old('editing') == $person->id) open @endif>
                            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-5 py-4">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-text">
                                        {{ $person->name }}
                                        @if ($person->is($actor))
                                            <span class="text-xs font-normal text-text-muted">({{ __('you') }})</span>
                                        @endif
                                    </span>
                                    <span class="block truncate text-xs text-text-muted">{{ $person->email }}</span>
                                </span>
                                <span class="flex flex-wrap items-center gap-1.5">
                                    <x-badge :tone="($person->role?->level ?? 0) >= 80 ? 'gold' : 'info'">{{ $person->role?->name_en ?? __('No role') }}</x-badge>
                                    @if ($person->two_factor_confirmed_at)
                                        <x-badge tone="good">{{ __('Two-step on') }}</x-badge>
                                    @endif
                                    @if ($person->staffProfile)
                                        <x-badge>{{ __('Rota: :name', ['name' => $person->staffProfile->full_name]) }}</x-badge>
                                    @endif
                                    @if (! $person->is_active)
                                        <x-badge tone="bad">{{ __('Switched off') }}</x-badge>
                                    @endif
                                </span>
                            </summary>

                            <div class="border-t border-line px-5 py-4">
                                @if (! $editable)
                                    <p class="text-sm text-text-muted">
                                        {{ $person->is($actor)
                                            ? __('Change your own password and security from your Account page.')
                                            : __('Only the owner can change management and owner logins.') }}
                                    </p>
                                @else
                                    <form method="POST" action="{{ route('users.update', $person) }}" class="grid gap-3 sm:grid-cols-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="editing" value="{{ $person->id }}">
                                        <x-field name="name" :label="__('Name')" :value="$person->name" required />
                                        <x-field name="email" type="email" :label="__('Email')" :value="$person->email" required />
                                        <x-field name="role_id" type="select" :label="__('Role')" :value="$person->role_id" required
                                                 :options="$roles->mapWithKeys(fn ($role) => [$role->id => $role->name_en.' ('.$role->level.')'])->all()" />
                                        <x-field name="staff_profile_id" type="select" :label="__('Their rota record')" :value="$person->staffProfile?->id"
                                                 :hint="__('Lets them see their own timesheet')"
                                                 :options="['' => __('Not linked')] + $unlinkedStaff->concat($person->staffProfile ? [$person->staffProfile] : [])->unique('id')->mapWithKeys(fn ($s) => [$s->id => $s->full_name])->all()" />
                                        <x-field name="is_active" type="checkbox" :label="__('Can sign in')" :value="$person->is_active"
                                                 :hint="__('Can sign in (untick to switch this login off)')" class="sm:col-span-2" />
                                        <div class="sm:col-span-2">
                                            <x-btn type="submit" size="sm">{{ __('Save') }}</x-btn>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('users.password', $person) }}" class="mt-5 grid gap-3 border-t border-line pt-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                                        @csrf
                                        <input type="hidden" name="editing" value="{{ $person->id }}">
                                        <x-field name="password" type="password" :label="__('New password')" autocomplete="new-password" required />
                                        <x-field name="password_confirmation" type="password" :label="__('Type it again')" autocomplete="new-password" required />
                                        <x-btn type="submit" variant="secondary" size="sm" :confirm="__('Set a new password? They will be signed out everywhere.')">{{ __('Reset password') }}</x-btn>
                                    </form>
                                @endif
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>

        <aside>
            <section class="card p-5">
                <h2 class="text-sm font-semibold text-text">{{ __('Create a login') }}</h2>
                <p class="mt-1 text-xs text-text-muted">{{ __('Choose a starting password and give it to them in person. They can change it from their Account page.') }}</p>

                <form method="POST" action="{{ route('users.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <x-field name="name" :label="__('Name')" required />
                    <x-field name="email" type="email" :label="__('Email')" required />
                    <x-field name="role_id" type="select" :label="__('Role')" required
                             :options="$roles->mapWithKeys(fn ($role) => [$role->id => $role->name_en.' ('.$role->level.')'])->all()" />
                    <x-field name="staff_profile_id" type="select" :label="__('Their rota record')"
                             :hint="__('Pick them if they are on the Staff page, so they can see their own timesheet')"
                             :options="['' => __('Not linked')] + $unlinkedStaff->mapWithKeys(fn ($s) => [$s->id => $s->full_name])->all()" />
                    <x-field name="password" type="password" :label="__('Starting password')" :hint="__('At least 12 characters')" autocomplete="new-password" required />
                    <x-field name="password_confirmation" type="password" :label="__('Type it again')" autocomplete="new-password" required />
                    <x-btn type="submit" icon="plus">{{ __('Create login') }}</x-btn>
                </form>
            </section>
        </aside>
    </div>
@endsection
