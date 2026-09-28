{{-- Branded cursor ring. The system cursor stays visible underneath. --}}
<div aria-hidden="true" class="pointer-events-none fixed inset-0 z-[190] hidden lg:block">
    <div data-cursor-ring
         class="absolute top-0 left-0 flex h-[30px] w-[30px] items-center justify-center rounded-full border opacity-0"
         style="border-color: color-mix(in oklab, var(--color-gold) 78%, transparent); translate: -50% -50%">
        <span data-cursor-label class="text-[0.58rem] font-bold tracking-[0.2em] text-ink uppercase opacity-0"></span>
    </div>
</div>

{{-- Route transition cover: a navy panel behind a curved leading edge. --}}
<div data-transition aria-hidden="true"
     class="pointer-events-none fixed inset-x-0 top-0 z-[110] hidden h-[100svh] bg-navy"
     style="clip-path: polygon(0% 0%, 25% 0%, 50% 0%, 75% 0%, 100% 0%, 100% 100%, 0% 100%)">
    <div class="brand-pattern absolute inset-0 opacity-[0.06]"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-royal-deep/25 via-transparent to-royal-deep/20"></div>
    <div data-transition-mark class="absolute inset-0 flex flex-col items-center justify-center gap-5 opacity-0">
        <img src="{{ asset('img/midland-logo.webp') }}" alt="" width="64" height="64" class="h-16 w-16 object-contain">
        <span class="eyebrow text-cream/58">Midland Catering</span>
    </div>
</div>
