@php
    $flashes = array_filter([
        'good' => session('status'),
        'bad' => session('error'),
        'warn' => session('warning'),
    ]);
@endphp

@foreach ($flashes as $tone => $message)
    @php
        $styles = [
            'good' => 'border-good/30 bg-good-bg text-good',
            'bad' => 'border-bad/30 bg-bad-bg text-bad',
            'warn' => 'border-warn/30 bg-warn-bg text-warn',
        ][$tone];
        $icons = ['good' => 'check-circle', 'bad' => 'alert', 'warn' => 'alert'][$tone];
    @endphp

    <div role="status" class="rise mb-5 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm font-medium {{ $styles }}">
        <x-icon :name="$icons" class="mt-0.5 size-4 shrink-0" />
        <span class="min-w-0 flex-1">{{ $message }}</span>
        <button type="button" data-dismiss class="tap -my-2 -me-2 grid shrink-0 place-items-center rounded-lg opacity-60 transition-opacity hover:opacity-100">
            <x-icon name="x" class="size-3.5" />
            <span class="sr-only">{{ __('Dismiss') }}</span>
        </button>
    </div>
@endforeach

{{-- Validation errors that no single field owns (business-rule failures). --}}
@if ($errors->any() && $errors->has('form'))
    <div role="alert" class="mb-5 rounded-xl border border-bad/30 bg-bad-bg px-4 py-3 text-sm font-medium text-bad">
        {{ $errors->first('form') }}
    </div>
@endif
