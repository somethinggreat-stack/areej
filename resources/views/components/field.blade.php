@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'options' => null,
    'rows' => 3,
    'suffix' => null,
    'prefix' => null,
])

@php
    $id = $name.'-'.substr(md5($name.$label), 0, 5);

    // Bracketed names (items[0][qty]) have to be dotted for old() and errors.
    $key = str_replace(['[', ']'], ['.', ''], rtrim($name, '[]'));
    $current = old($key, $value);
    $invalid = $errors->has($key) || $errors->has($name);
    $message = $errors->first($key) ?: $errors->first($name);

    // number/date/time controls are pinned to LTR by the .tabular-nums base rule
    // so a price or a phone number never reads backwards. That also pins their
    // padding to the physical edges, so a prefix or suffix has to be placed
    // physically too — a logical start-3/end-3 lands on top of the value on an
    // RTL page.
    $ltrControl = in_array($type, ['number', 'date', 'time'], true);

    $control = 'w-full rounded-lg border bg-surface px-3 py-2 text-sm text-text outline-none transition-colors '
        .'placeholder:text-text-faint focus:border-royal-lit focus:ring-2 focus:ring-royal-lit/25 '
        .'disabled:opacity-50 '
        .($invalid ? 'border-bad' : 'border-line-strong');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'min-w-0']) }}>
    @if ($type !== 'checkbox')
        <label for="{{ $id }}" class="mb-1.5 block text-xs font-semibold text-text-muted">
            {{ $label }}
            @if ($required)
                <span class="text-bad" aria-hidden="true">*</span>
                <span class="sr-only">{{ __('required') }}</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if ($type === 'select')
            <select id="{{ $id }}" name="{{ $name }}" @required($required)
                    @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
                    {{ $attributes->except('class')->merge(['class' => 'tap '.$control]) }}>
                @foreach ($options ?? [] as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>

        @elseif ($type === 'textarea')
            <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
                      @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
                      {{ $attributes->except('class')->merge(['class' => $control]) }}>{{ $current }}</textarea>

        @elseif ($type === 'checkbox')
            <label for="{{ $id }}" class="tap flex cursor-pointer items-center gap-2.5 text-sm text-text">
                <input type="hidden" name="{{ $name }}" value="0">
                <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" @checked((bool) $current)
                       {{ $attributes->except('class') }}
                       class="size-4 shrink-0 rounded border-line-strong bg-surface text-gold focus:ring-2 focus:ring-gold/40">
                <span>{{ $hint ?? $label }}</span>
            </label>

        @else
            @if ($prefix)
                <span aria-hidden="true" class="pointer-events-none absolute inset-y-0 {{ $ltrControl ? 'left-3' : 'start-3' }} flex items-center text-sm text-text-faint">{{ $prefix }}</span>
            @endif

            <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $current }}" @required($required)
                   @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
                   @if ($type === 'number') inputmode="decimal" @endif
                   @if ($type === 'tel') inputmode="tel" @endif
                   {{ $attributes->except('class')->merge([
                        'class' => 'tap '.$control
                            .($suffix ? (mb_strlen($suffix) > 4 ? ' pe-16' : ' pe-11') : '')
                            .($prefix ? ' ps-7' : '')
                            .(in_array($type, ['number', 'date', 'time'], true) ? ' tabular-nums' : ''),
                   ]) }}>

            @if ($suffix)
                <span aria-hidden="true" class="pointer-events-none absolute inset-y-0 {{ $ltrControl ? 'right-3' : 'end-3' }} flex items-center text-xs text-text-faint">{{ $suffix }}</span>
            @endif
        @endif
    </div>

    @if ($hint && $type !== 'checkbox')
        <p class="mt-1 text-xs text-text-faint">{{ $hint }}</p>
    @endif

    @if ($invalid)
        <p id="{{ $id }}-error" class="mt-1 text-xs font-medium text-bad">{{ $message }}</p>
    @endif
</div>
