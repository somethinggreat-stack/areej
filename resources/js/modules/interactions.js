import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* -------------------------------------------------------------------------
 * Branded page transition.
 *
 * A navy panel sweeps up behind a curved leading edge, the emblem fades in,
 * then the browser navigates. Modifier-clicks and new-tab clicks are left
 * alone so cmd/middle-click keep working exactly as people expect.
 * ---------------------------------------------------------------------- */
export function initPageTransitions() {
    const panel = document.querySelector('[data-transition]');
    if (!panel || reduced()) return;

    // Reveal on arrival.
    const mark = panel.querySelector('[data-transition-mark]');
    gsap.set(panel, { display: 'block', yPercent: 0 });
    gsap.timeline({ onComplete: () => gsap.set(panel, { display: 'none' }) })
        .to(mark, { autoAlpha: 0, duration: 0.28 }, 0.05)
        .to(panel, { yPercent: -100, duration: 0.78, ease: 'expo.inOut' }, 0.14);

    let leaving = false;

    document.addEventListener('click', (e) => {
        const link = e.target?.closest?.('a[href]');
        if (!link || leaving) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
        if (link.target === '_blank' || link.hasAttribute('download')) return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        if (url.pathname === window.location.pathname && url.hash) return;
        if (link.hasAttribute('data-no-transition')) return;

        e.preventDefault();
        leaving = true;

        gsap.timeline()
            .set(panel, { display: 'block', yPercent: 100 })
            .set(mark, { autoAlpha: 0, scale: 0.86 })
            .to(panel, { yPercent: 0, duration: 0.62, ease: 'expo.inOut' })
            .to(mark, { autoAlpha: 1, scale: 1, duration: 0.4, ease: 'expo.out' }, '-=0.34')
            .call(() => { window.location.href = url.href; });
    });

    // Coming back via history should never land behind the cover.
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) {
            leaving = false;
            gsap.set(panel, { display: 'none' });
        }
    });
}

/* -------------------------------------------------------------------------
 * Counters that tick up when they arrive.
 * ---------------------------------------------------------------------- */
export function initCounters() {
    document.querySelectorAll('[data-count-to]').forEach((el) => {
        const to = Number(el.dataset.countTo);
        const suffix = el.dataset.countSuffix || '';

        if (reduced()) {
            el.textContent = to.toLocaleString('en-GB') + suffix;
            return;
        }

        const obj = { v: 0 };
        gsap.to(obj, {
            v: to,
            duration: 2.1,
            ease: 'expo.out',
            scrollTrigger: { trigger: el, start: 'top 90%', once: true },
            onUpdate: () => {
                el.textContent = Math.round(obj.v).toLocaleString('en-GB') + suffix;
            },
        });
    });
}

/* -------------------------------------------------------------------------
 * Opening statement: the sentence lights up word by word on scroll.
 * ---------------------------------------------------------------------- */
export function initStatement() {
    const sentence = document.querySelector('[data-statement]');
    if (!sentence || reduced()) return;

    const words = sentence.querySelectorAll('[data-word]');
    if (!words.length) return;

    // The unlit state stays above 4.5:1 on the ink ground — this is a shift in
    // emphasis, never a passage of unreadable text.
    gsap.fromTo(
        words,
        { color: 'color-mix(in oklab, var(--color-cream) 46%, transparent)' },
        {
            color: 'var(--color-cream)',
            stagger: 0.25,
            ease: 'none',
            scrollTrigger: { trigger: sentence, start: 'top 76%', end: 'bottom 58%', scrub: 0.7 },
        }
    );
}

/* -------------------------------------------------------------------------
 * Pinned brand story: the stage pins while chapters advance, and the whole
 * section evolves through each chapter's colour.
 * ---------------------------------------------------------------------- */
export function initPinnedStory() {
    const root = document.querySelector('[data-story]');
    if (!root) return;

    const stage = root.querySelector('[data-story-stage]');
    const chapters = Array.from(root.querySelectorAll('[data-chapter]'));
    const rails = Array.from(root.querySelectorAll('[data-chapter-rail]'));
    const bodies = Array.from(root.querySelectorAll('[data-chapter-body]'));
    if (!stage || !chapters.length) return;

    // Mobile keeps a plain vertical sequence — a faked pin on a phone is worse
    // than no pin at all.
    if (reduced() || window.innerWidth < 1024) {
        chapters.forEach((c) => (c.style.opacity = '1'));
        return;
    }

    const tones = JSON.parse(root.dataset.tones || '{}');
    const toneNames = chapters.map((c) => c.dataset.tone || 'navy');
    let active = -1;

    const applyTone = (i) => {
        const tone = tones[toneNames[i]];
        if (!tone) return;
        gsap.to(root, { backgroundColor: tone.bg, duration: 0.85, ease: 'power2.inOut' });
        gsap.to(root.querySelectorAll('[data-tone-text]'), { color: tone.text, duration: 0.85, ease: 'power2.inOut' });
        gsap.to(root.querySelectorAll('[data-tone-muted]'), { color: tone.muted, duration: 0.85, ease: 'power2.inOut' });

        // Gold sits at ~1.9:1 on the cream chapter, so the accent moves with
        // the tone rather than staying fixed.
        if (tone.accent) {
            gsap.to(root.querySelectorAll('[data-tone-accent]'), { color: tone.accent, duration: 0.85, ease: 'power2.inOut' });
            gsap.to(root.querySelectorAll('[data-tone-accent-bg]'), { backgroundColor: tone.accent, duration: 0.85, ease: 'power2.inOut' });
            gsap.to(root.querySelectorAll('[data-tone-accent-border]'), { borderColor: tone.accent + '66', duration: 0.85, ease: 'power2.inOut' });
        }
    };

    const show = (i) => {
        if (i === active) return;
        active = i;
        applyTone(i);

        chapters.forEach((c, n) => {
            gsap.to(c, {
                autoAlpha: n === i ? 1 : 0,
                scale: n === i ? 1 : 1.08,
                duration: 1,
                ease: 'expo.out',
            });
            c.style.clipPath = n === i ? 'inset(0% 0% 0% 0%)' : 'inset(0% 0% 100% 0%)';
        });

        bodies.forEach((b, n) => {
            gsap.to(b, { autoAlpha: n === i ? 1 : 0, y: n === i ? 0 : 22, duration: 0.7, ease: 'expo.out' });
        });

        rails.forEach((r, n) => {
            r.style.opacity = n === i ? '1' : '0.55';
            const fill = r.querySelector('[data-rail-fill]');
            if (fill) fill.style.transform = `scaleX(${n === i ? 1 : 0})`;
        });
    };

    ScrollTrigger.create({
        trigger: root,
        start: 'top top',
        end: () => `+=${window.innerHeight * chapters.length * 0.92}`,
        pin: stage,
        pinSpacing: true,
        anticipatePin: 1,
        invalidateOnRefresh: true,
        onUpdate: (self) => {
            show(gsap.utils.clamp(0, chapters.length - 1, Math.floor(self.progress * chapters.length * 0.999)));
        },
    });

    show(0);
}

/* -------------------------------------------------------------------------
 * Services list. The intro column is sticky in CSS; the cards only need to
 * arrive as they reach the viewport.
 * ---------------------------------------------------------------------- */
export function initServicesJourney() {
    const root = document.querySelector('[data-journey]');
    if (!root || reduced()) return;

    root.querySelectorAll('[data-service-card]').forEach((card) => {
        gsap.fromTo(card,
            { autoAlpha: 0, y: 46 },
            {
                autoAlpha: 1,
                y: 0,
                duration: 1,
                ease: 'expo.out',
                scrollTrigger: { trigger: card, start: 'top 88%', once: true },
            });
    });
}

/* -------------------------------------------------------------------------
 * Process timeline: sticky imagery, a drawn connector, stepping numbers.
 * ---------------------------------------------------------------------- */
export function initProcessTimeline() {
    const root = document.querySelector('[data-process]');
    if (!root) return;

    const steps = Array.from(root.querySelectorAll('[data-step]'));
    const images = Array.from(root.querySelectorAll('[data-step-image]'));
    const numbers = Array.from(root.querySelectorAll('[data-step-number]'));
    const dots = Array.from(root.querySelectorAll('[data-step-dot]'));
    const line = root.querySelector('[data-process-line]');
    if (!steps.length) return;

    if (reduced()) {
        steps.forEach((s) => (s.style.opacity = '1'));
        return;
    }

    if (line) {
        gsap.fromTo(line, { scaleY: 0 }, {
            scaleY: 1, ease: 'none',
            scrollTrigger: { trigger: root.querySelector('[data-steps]'), start: 'top 68%', end: 'bottom 78%', scrub: 0.5 },
        });
    }

    const activate = (i) => {
        images.forEach((img, n) => gsap.to(img, { autoAlpha: n === i ? 1 : 0, scale: n === i ? 1 : 1.07, duration: 1, ease: 'expo.out' }));
        numbers.forEach((num, n) => gsap.to(num, { autoAlpha: n === i ? 1 : 0, y: n === i ? 0 : (n < i ? -40 : 40), duration: 0.7, ease: 'expo.out' }));
        dots.forEach((dot, n) => {
            dot.style.width = n === i ? '2.5rem' : '1rem';
            dot.style.backgroundColor = n === i ? 'var(--color-gold)' : 'rgba(3,8,28,0.15)';
        });
    };

    steps.forEach((step, i) => {
        gsap.fromTo(step.querySelectorAll('[data-step-fade]'),
            { autoAlpha: 0, y: 40 },
            { autoAlpha: 1, y: 0, duration: 0.95, stagger: 0.06, ease: 'expo.out',
              scrollTrigger: { trigger: step, start: 'top 82%', once: true } });

        ScrollTrigger.create({
            trigger: step, start: 'top 62%', end: 'bottom 62%',
            onEnter: () => activate(i),
            onEnterBack: () => activate(i),
        });
    });

    activate(0);
}

/* -------------------------------------------------------------------------
 * Scroll-driven collage: frames fly in from different edges and settle.
 * ---------------------------------------------------------------------- */
export function initCollage() {
    const root = document.querySelector('[data-collage]');
    if (!root || reduced()) return;

    root.querySelectorAll('[data-piece]').forEach((piece) => {
        const { fx = 0, fy = 0, fr = 0, depth = 0 } = piece.dataset;

        gsap.fromTo(piece,
            { x: Number(fx), y: Number(fy), rotate: Number(fr), autoAlpha: 0, scale: 0.86 },
            {
                x: 0, y: 0, rotate: 0, autoAlpha: 1, scale: 1, ease: 'expo.out',
                scrollTrigger: { trigger: root, start: 'top 82%', end: 'center 58%', scrub: 0.9 },
            });

        const inner = piece.querySelector('[data-piece-img]');
        if (inner) {
            gsap.fromTo(inner, { yPercent: -Number(depth) / 2 }, {
                yPercent: Number(depth) / 2, ease: 'none',
                scrollTrigger: { trigger: root, start: 'center 70%', end: 'bottom top', scrub: 1 },
            });
        }
    });
}

/* -------------------------------------------------------------------------
 * Menu spotlight: the course index drives one large frame. Pointer and
 * keyboard both count, so tabbing through the list swaps the image too.
 * ---------------------------------------------------------------------- */
export function initSpotlight() {
    const root = document.querySelector('[data-spotlight]');
    if (!root) return;

    const items = [...root.querySelectorAll('[data-spotlight-item]')];
    const panels = [...root.querySelectorAll('[data-spotlight-panel]')];
    if (items.length === 0) return;

    // The frame only exists from lg up. Below that the list carries its own
    // thumbnails, so a permanently highlighted first row would just be noise.
    if (window.innerWidth < 1024) return;

    let current = 0;

    const show = (index) => {
        if (index === current) return;

        const outgoing = panels[current];
        const incoming = panels[index];
        const downward = index > current;
        current = index;

        items.forEach((item, i) => {
            const fill = item.querySelector('[data-spot-fill]');
            if (!fill) return;
            gsap.to(fill, {
                scaleX: i === index ? 1 : 0,
                duration: reduced() ? 0 : 0.7,
                ease: 'expo.out',
                transformOrigin: i === index ? 'left center' : 'right center',
            });
        });

        if (!incoming) return;

        if (reduced()) {
            panels.forEach((p) => gsap.set(p, { opacity: p === incoming ? 1 : 0, clipPath: 'inset(0%)' }));
            return;
        }

        // The new frame wipes in over the old one, then the old one is parked.
        gsap.set(incoming, {
            opacity: 1,
            zIndex: 2,
            clipPath: downward ? 'inset(100% 0% 0% 0%)' : 'inset(0% 0% 100% 0%)',
        });
        gsap.set(outgoing, { zIndex: 1 });

        gsap.to(incoming, {
            clipPath: 'inset(0% 0% 0% 0%)',
            duration: 0.95,
            ease: 'expo.inOut',
            onComplete: () => {
                panels.forEach((p) => {
                    if (p !== incoming) gsap.set(p, { opacity: 0, zIndex: 0 });
                });
            },
        });

        const img = incoming.querySelector('img');
        if (img) gsap.fromTo(img, { scale: 1.12 }, { scale: 1.03, duration: 1.4, ease: 'expo.out' });
    };

    items.forEach((item, index) => {
        item.addEventListener('pointerenter', () => show(index));
        item.addEventListener('focus', () => show(index));
    });

    // Prime the first row so the list never starts with nothing highlighted.
    const firstFill = items[0].querySelector('[data-spot-fill]');
    if (firstFill) gsap.set(firstFill, { scaleX: 1, transformOrigin: 'left center' });
}
