{{-- Sticky action bar. On a phone this keeps the primary action reachable
     without scrolling back up a long list. --}}
<div {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-center gap-2']) }}>
    {{ $slot }}
</div>
