@props(['tabs' => [], 'current' => '', 'param' => 'view'])

<div class="no-scrollbar mb-4 flex gap-2 overflow-x-auto pb-0.5">
    @foreach ($tabs as $key => $label)
        <a href="{{ request()->fullUrlWithQuery([$param => $key ?: null, 'page' => null]) }}"
           @if ((string) $current === (string) $key) aria-current="page" @endif
           class="tap inline-flex shrink-0 items-center rounded-lg px-3.5 text-xs font-semibold whitespace-nowrap transition-colors
                  {{ (string) $current === (string) $key
                        ? 'bg-navy text-cream'
                        : 'border border-line bg-surface text-text-muted hover:bg-surface-2 hover:text-text' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
