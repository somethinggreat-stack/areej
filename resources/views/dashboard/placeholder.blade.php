@extends('layouts.app')

@section('title', __($heading))

@section('content')
    <div class="card p-8 text-center">
        <h2 class="text-base font-semibold text-text">{{ __($heading) }}</h2>
        <p class="mx-auto mt-2 max-w-md text-sm text-text-muted">
            {{ __('Coming in the next phase. The foundation, roles and database for this section are already in place.') }}
        </p>
    </div>
@endsection
