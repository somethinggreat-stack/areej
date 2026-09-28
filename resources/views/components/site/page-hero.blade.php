@props([
    'eyebrow' => '',
    'lines' => [],
    'lede' => null,
    'image' => 'karahi-naan',
    'meta' => [],
    'crumb' => null,
])

<section data-hero class="grain relative isolate flex min-h-[70svh] items-end overflow-hidden bg-ink pt-32 pb-14 sm:min-h-[76svh] sm:pt-40 sm:pb-16">
    <div data-bg class="absolute inset-0 -z-10" style="clip-path: inset(0% 0% 100% 0%)">
        <div data-bg-inner class="absolute inset-[-7%]">
            <x-site.img :name="$image" sizes="100vw" priority class="h-full w-full object-cover" />
        </div>
        <span data-scrim aria-hidden="true" class="absolute inset-0 bg-ink/62"></span>
        <span aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-ink via-ink/55 to-ink/25"></span>
        <span aria-hidden="true" class="absolute inset-0 bg-gradient-to-r from-ink/85 to-transparent"></span>
    </div>

    <div class="container-wide relative">
        @if ($crumb)
            <a href="{{ $crumb['url'] }}" class="group/bc mb-6 inline-flex items-center gap-2.5 text-[0.66rem] font-semibold tracking-[0.2em] text-cream/58 uppercase transition-colors hover:text-gold">
                <svg viewBox="0 0 24 24" class="size-3.5 transition-transform duration-500 group-hover/bc:-translate-x-1" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5M11 18l-6-6 6-6" />
                </svg>
                {{ $crumb['label'] }}
            </a>
        @endif

        <span data-eyebrow class="line-mask inline-block">
            <span class="eyebrow text-gold">
                <span aria-hidden="true" class="mr-3.5 inline-block h-px w-10 -translate-y-[0.32em] bg-gold/70"></span>{{ $eyebrow }}
            </span>
        </span>

        <h1 class="display-xl mt-6 max-w-5xl text-cream">
            @foreach ($lines as $line)
                <span data-line class="line-mask"><span>{{ $line }}</span></span>
            @endforeach
        </h1>

        <div data-rule aria-hidden="true" class="mt-8 h-px w-full max-w-sm origin-left bg-gradient-to-r from-gold via-gold/40 to-transparent"></div>

        @if ($lede)
            <p data-copy class="body-lg mt-7 max-w-2xl text-cream/70">{{ $lede }}</p>
        @endif

        @if (count($meta))
            <ul data-tags class="mt-9 flex flex-wrap gap-2">
                @foreach ($meta as $m)
                    <li class="rounded-full border border-cream/18 bg-ink/25 px-4 py-2 text-[0.64rem] font-semibold tracking-[0.2em] text-cream/65 uppercase backdrop-blur-sm">{{ $m }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
