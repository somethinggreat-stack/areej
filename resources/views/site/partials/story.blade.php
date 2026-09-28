@php
    $chapters = config('story.chapters');
    $tones = config('story.tones');
@endphp

{{-- Pinned brand story. Desktop pins the stage and advances chapters while the
     whole section evolves through each chapter's colour. Under 1024px it falls
     back to a plain vertical sequence — a faked pin on a phone is worse than
     none, and the JS bails out at that width. --}}
<section data-story
         data-tones='@json($tones)'
         class="relative lg:min-h-[100svh]"
         style="background-color: {{ $tones[$chapters[0]['tone']]['bg'] }}"
         aria-label="Our story">

    {{-- ---------------------------- Desktop ---------------------------- --}}
    <div data-story-stage class="relative hidden h-[100svh] overflow-hidden lg:block">
        <div aria-hidden="true" class="brand-pattern pointer-events-none absolute inset-0 opacity-[0.05]"></div>

        <div class="container-wide relative flex h-full items-center">
            <div class="grid w-full grid-cols-[0.92fr_1.08fr] items-center gap-16">
                <div>
                    <span data-tone-muted class="eyebrow flex items-center gap-3.5" style="color: {{ $tones[$chapters[0]['tone']]['muted'] }}">
                        <span data-tone-accent-bg aria-hidden="true" class="h-px w-12" style="background-color: {{ $tones[$chapters[0]['tone']]['accent'] }}"></span> Our story
                    </span>

                    <h2 data-tone-text class="display-xl mt-7" style="color: {{ $tones[$chapters[0]['tone']]['text'] }}">
                        Three things<br>we get right.
                    </h2>

                    <ol class="mt-14 space-y-1">
                        @foreach ($chapters as $chapter)
                            <li>
                                <div data-chapter-rail class="flex items-center gap-5 py-3 transition-opacity duration-500" style="opacity: {{ $loop->first ? '1' : '0.55' }}">
                                    <span data-tone-muted class="w-6 font-sans text-[0.62rem] font-semibold tracking-[0.2em] tabular-nums" style="color: {{ $tones[$chapters[0]['tone']]['muted'] }}">
                                        0{{ $loop->iteration }}
                                    </span>
                                    <span class="relative h-px flex-1 overflow-hidden bg-current opacity-25">
                                        <span data-rail-fill data-tone-accent-bg class="absolute inset-y-0 left-0 w-full origin-left transition-transform duration-700 ease-[cubic-bezier(0.16,1,0.3,1)]" 
                                              style="transform: scaleX({{ $loop->first ? 1 : 0 }}); background-color: {{ $tones[$chapters[0]['tone']]['accent'] }}"></span>
                                    </span>
                                    <span data-tone-text class="w-56 shrink-0 text-[0.78rem] font-semibold tracking-[0.12em] uppercase" style="color: {{ $tones[$chapters[0]['tone']]['text'] }}">
                                        {{ $chapter['title'] }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="relative">
                    <div class="relative h-[min(58svh,34rem)] w-full max-w-xl overflow-hidden">
                        @foreach ($chapters as $chapter)
                            <div data-chapter data-tone="{{ $chapter['tone'] }}"
                                 class="absolute inset-0 transition-[clip-path] duration-[1100ms] ease-[cubic-bezier(0.16,1,0.3,1)]"
                                 style="opacity: {{ $loop->first ? 1 : 0 }}; clip-path: inset(0% 0% {{ $loop->first ? '0%' : '100%' }} 0%); z-index: {{ $loop->first ? 2 : 1 }}">
                                <x-site.img :name="$chapter['image']" :alt="$chapter['title']" sizes="(min-width: 1024px) 45vw, 0px" class="h-full w-full object-cover" />
                                <span aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-ink/70 via-ink/10 to-transparent"></span>
                            </div>
                        @endforeach
                        <span data-tone-accent-border aria-hidden="true" class="pointer-events-none absolute inset-4 z-10 border" style="border-color: {{ $tones[$chapters[0]['tone']]['accent'] }}66"></span>
                    </div>

                    <div class="relative mt-8 min-h-[8rem] max-w-xl">
                        @foreach ($chapters as $chapter)
                            <div data-chapter-body class="absolute inset-x-0 top-0" style="opacity: {{ $loop->first ? 1 : 0 }}">
                                <span data-tone-accent class="eyebrow" style="color: {{ $tones[$chapters[0]['tone']]['accent'] }}">{{ $chapter['kicker'] }}</span>
                                <p data-tone-muted class="mt-3 text-[1.02rem] leading-[1.7]" style="color: {{ $tones[$chapters[0]['tone']]['muted'] }}">
                                    {{ $chapter['body'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ----------------------------- Mobile ---------------------------- --}}
    <div class="bg-navy py-20 lg:hidden">
        <div class="container-x">
            <span class="eyebrow flex items-center gap-3.5 text-gold">
                <span aria-hidden="true" class="h-px w-10 bg-gold/70"></span> Our story
            </span>
            <h2 class="display-lg mt-6 text-cream">Three things we get right.</h2>
        </div>

        <div class="mt-14 space-y-20">
            @foreach ($chapters as $chapter)
                <article class="container-x">
                    <figure data-mask-image class="relative aspect-[4/3] w-full overflow-hidden bg-ink" style="clip-path: inset(100% 0% 0% 0%)">
                        <div data-mask-inner class="h-full w-full">
                            <x-site.img :name="$chapter['image']" :alt="$chapter['title']" sizes="(max-width: 1023px) 90vw, 0px" class="h-full w-full object-cover" />
                        </div>
                        <span aria-hidden="true" class="pointer-events-none absolute inset-3 border border-gold/45"></span>
                    </figure>
                    <div class="mt-7">
                        <span class="eyebrow flex items-center gap-3 text-gold">
                            <span class="font-display text-2xl leading-none font-light tabular-nums">0{{ $loop->iteration }}</span>
                            <span class="h-px w-8 bg-gold/50"></span>
                            {{ $chapter['kicker'] }}
                        </span>
                        <h3 class="display-md mt-4 text-cream">{{ $chapter['title'] }}</h3>
                        <p class="mt-4 leading-relaxed text-cream/62">{{ $chapter['body'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
