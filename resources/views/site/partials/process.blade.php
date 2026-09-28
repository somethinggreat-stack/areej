@php
    $steps = config('story.process');
@endphp

{{-- Event process. Desktop keeps a sticky image column that cross-fades as you
     move through the steps, with a gold connector drawing itself down the
     column. Mobile drops the sticky column and shows each step's image inline. --}}
<section data-process class="relative isolate overflow-hidden bg-cream py-24 text-ink sm:py-32" aria-label="How we work">
    <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.05]"></div>

    <div class="container-x relative">
        <div class="max-w-2xl">
            <span class="eyebrow flex items-center gap-3.5 text-gold-ink">
                <span aria-hidden="true" class="h-px w-10 bg-gold-ink/70"></span> How it works
            </span>
            <h2 data-split class="display-xl mt-6 text-ink" style="visibility:hidden">From first call<br>to last plate.</h2>
            <p class="mt-6 max-w-lg text-ink/60">
                No account managers, no ticket systems. You speak to the people who will be
                cooking and serving on the day.
            </p>
        </div>

        <div class="mt-16 grid gap-14 lg:mt-20 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20">
            {{-- Sticky media column, desktop only --}}
            <div class="relative hidden lg:block">
                <div class="sticky top-[16vh]">
                    <div class="relative aspect-[4/5] w-full overflow-hidden bg-sand">
                        @foreach ($steps as $step)
                            <x-site.img data-step-image :name="$step['image']" :alt="$step['title']"
                                        sizes="(min-width: 1024px) 32vw, 0px"
                                        class="absolute inset-0 h-full w-full object-cover"
                                        style="opacity: {{ $loop->first ? 1 : 0 }}" />
                        @endforeach
                        <span aria-hidden="true" class="pointer-events-none absolute inset-4 border border-gold/50"></span>

                        {{-- Step number morphs as the active step changes --}}
                        <div class="absolute bottom-0 left-0 flex h-24 w-24 items-center justify-center overflow-hidden bg-cream">
                            @foreach ($steps as $step)
                                <span data-step-number class="font-display absolute text-4xl leading-none font-light text-ink tabular-nums"
                                      style="opacity: {{ $loop->first ? 1 : 0 }}">{{ $step['n'] }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-7 flex items-center gap-2.5">
                        @foreach ($steps as $step)
                            <span data-step-dot class="h-[3px] rounded-full transition-all duration-700 ease-[cubic-bezier(0.16,1,0.3,1)]"
                                  style="width: {{ $loop->first ? '2.5rem' : '1rem' }}; background-color: {{ $loop->first ? 'var(--color-gold)' : 'rgba(3,8,28,0.15)' }}"></span>
                        @endforeach
                        <span class="ml-3 text-[0.65rem] font-semibold tracking-[0.2em] text-ink/60 uppercase tabular-nums">
                            {{ count($steps) }} steps
                        </span>
                    </div>
                </div>
            </div>

            {{-- Steps --}}
            <ol data-steps class="relative">
                <span aria-hidden="true" class="absolute top-2 bottom-2 left-[1.15rem] w-px bg-ink/10 sm:left-[1.6rem]">
                    <span data-process-line class="absolute inset-0 origin-top bg-gradient-to-b from-gold via-gold to-gold/30"></span>
                </span>

                @foreach ($steps as $step)
                    <li data-step class="relative pb-14 pl-14 last:pb-0 sm:pl-20">
                        <span aria-hidden="true" class="absolute top-1 left-0 grid size-[2.3rem] place-items-center rounded-full border border-ink/15 bg-cream text-ink/60 sm:size-[3.2rem]">
                            <span class="font-sans text-[0.6rem] font-bold tracking-[0.12em] tabular-nums">{{ $step['n'] }}</span>
                        </span>

                        <div data-step-fade>
                            <h3 class="display-md text-ink">{{ $step['title'] }}</h3>
                            <p class="mt-3 max-w-md leading-relaxed text-ink/60">{{ $step['body'] }}</p>
                        </div>

                        <figure data-step-fade class="relative mt-6 aspect-[3/2] w-full overflow-hidden lg:hidden">
                            <x-site.img :name="$step['image']" :alt="$step['title']" sizes="(max-width: 1023px) 90vw, 0px" class="h-full w-full object-cover" />
                            <span aria-hidden="true" class="pointer-events-none absolute inset-3 border border-gold/45"></span>
                        </figure>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
