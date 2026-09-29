import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import { DrawSVGPlugin } from 'gsap/DrawSVGPlugin';
import Lenis from 'lenis';
import {
    initPageTransitions, initCounters, initStatement,
    initPinnedStory, initServicesJourney, initProcessTimeline, initCollage, initSpotlight,
} from './modules/interactions';
import { initGallery, initTestimonials, initGuestPresets } from './modules/gallery';

gsap.registerPlugin(ScrollTrigger, SplitText, DrawSVGPlugin);
gsap.defaults({ ease: 'expo.out', duration: 1.1 });
ScrollTrigger.config({ ignoreMobileResize: true });

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* -------------------------------------------------------------------------
 * Smooth scrolling, driven from GSAP's ticker so there is a single RAF loop.
 * ---------------------------------------------------------------------- */
let lenis = null;

if (!reduced) {
    lenis = new Lenis({
        duration: 1.05,
        lerp: 0.11,
        smoothWheel: true,
        syncTouch: false,
        autoRaf: false,
        anchors: false,
    });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((t) => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(500, 33);
    document.fonts?.ready.then(() => ScrollTrigger.refresh());
}

const lockScroll = () => {
    lenis?.stop();
    document.body.style.overflow = 'hidden';
};

const unlockScroll = () => {
    lenis?.start();
    document.body.style.overflow = '';
};

/* -------------------------------------------------------------------------
 * Opening loader — first visit per session only.
 * ---------------------------------------------------------------------- */
function runLoader() {
    const el = document.querySelector('[data-loader]');

    if (!el) {
        document.documentElement.dataset.loaded = 'true';
        return Promise.resolve();
    }

    if (sessionStorage.getItem('mc-loader-seen') === '1' || reduced) {
        el.remove();
        document.documentElement.dataset.loaded = 'true';
        return Promise.resolve();
    }

    lockScroll();
    const q = gsap.utils.selector(el);
    const veil = q('[data-veil]')[0];
    const ring = q('[data-iris-ring]')[0];
    const counter = q('[data-count]')[0];
    const split = SplitText.create(q('[data-word]')[0], { type: 'chars', mask: 'chars' });

    // Far enough to clear the corners, so the iris leaves nothing behind.
    const radius = Math.hypot(window.innerWidth, window.innerHeight) / 2 + 60;
    gsap.set(ring, { width: radius * 2, height: radius * 2, xPercent: -50, yPercent: -50, scale: 0 });

    // Ambient counter-rotation, outside the timeline so a skip never jerks it.
    const spin = [
        gsap.to(q('[data-dial]'), { rotation: 360, svgOrigin: '0 0', duration: 48, ease: 'none', repeat: -1 }),
        gsap.to(q('[data-star]'), { rotation: -360, svgOrigin: '0 0', duration: 72, ease: 'none', repeat: -1 }),
    ];

    const meter = { value: 0 };

    return new Promise((resolve) => {
        const tl = gsap.timeline({
            onComplete: () => {
                spin.forEach((tween) => tween.kill());
                split.revert();
                sessionStorage.setItem('mc-loader-seen', '1');
                document.documentElement.dataset.loaded = 'true';
                unlockScroll();
                el.remove();
            },
        });

        if (window.innerWidth < 640) {
            tl.timeScale(1.4);
        }

        // Build: bloom, frame, the star drawing itself, the emblem, the name.
        tl.fromTo(q('[data-bloom]'), { autoAlpha: 0, scale: 0.8 }, { autoAlpha: 1, scale: 1, duration: 1.6, ease: 'power2.out' }, 0)
            .fromTo(q('[data-frame] > span'), { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.8, stagger: 0.05 }, 0.1)
            .fromTo(q('[data-meter]'), { autoAlpha: 0, y: 12 }, { autoAlpha: 1, y: 0, duration: 0.6 }, 0.2)
            .fromTo(q('[data-draw]'), { drawSVG: '50% 50%' }, { drawSVG: '0% 100%', duration: 1.3, ease: 'power3.inOut', stagger: 0.12 }, 0.1)
            .fromTo(q('[data-tick]'), { autoAlpha: 0, scale: 0, transformOrigin: '50% 50%' }, { autoAlpha: 1, scale: 1, duration: 0.5, stagger: 0.012 }, 0.35)
            .fromTo(q('[data-glow]'), { autoAlpha: 0, scale: 0.6 }, { autoAlpha: 1, scale: 1, duration: 1.2 }, 0.8)
            .fromTo(q('[data-logo]'), { autoAlpha: 0, scale: 0.7, filter: 'blur(14px)' }, { autoAlpha: 1, scale: 1, filter: 'blur(0px)', duration: 1.1 }, 0.85)
            .fromTo(split.chars, { yPercent: 110, rotate: 6 }, { yPercent: 0, rotate: 0, duration: 0.9, stagger: { each: 0.035, from: 'center' } }, 1.25)
            .fromTo(q('[data-word]'), { letterSpacing: '0.3em' }, { letterSpacing: '0.14em', duration: 1.6 }, 1.25)
            .fromTo(q('[data-rule]'), { scaleX: 0 }, { scaleX: 1, duration: 0.8, ease: 'expo.inOut' }, 1.7)
            .fromTo(q('[data-sub]'), { yPercent: 110 }, { yPercent: 0, duration: 0.7 }, 1.85)
            .to(meter, {
                value: 100,
                duration: 2.5,
                ease: 'power2.inOut',
                onUpdate: () => { counter.textContent = String(Math.round(meter.value)).padStart(3, '0'); },
            }, 0.2)
            .fromTo(q('[data-progress]'), { scaleX: 0 }, { scaleX: 1, duration: 2.5, ease: 'power2.inOut' }, 0.2)
            .to(q('[data-glow]'), { scale: 1.15, duration: 0.35, ease: 'power2.out', yoyo: true, repeat: 1 }, 2.7)

            // Exit: the name lifts away, the star flies outward, the iris opens.
            .addLabel('exit', 3.05)
            .to(split.chars, { yPercent: -110, duration: 0.55, ease: 'power3.in', stagger: { each: 0.02, from: 'edges' } }, 'exit')
            .to(q('[data-sub], [data-rule], [data-meter], [data-frame]'), { autoAlpha: 0, duration: 0.4, ease: 'power2.in' }, 'exit')
            .to(q('[data-emblem] svg'), { scale: 2.6, rotation: 30, autoAlpha: 0, duration: 1.2, ease: 'expo.in' }, 'exit')
            .to(q('[data-logo], [data-glow]'), { scale: 0.6, autoAlpha: 0, filter: 'blur(10px)', duration: 0.7, ease: 'power3.in' }, 'exit+=0.15')
            // Hand off to the hero while the iris is still opening, so it animates in behind it.
            .call(resolve, [], 'exit+=0.55')
            .to(veil, { '--iris': `${radius}px`, duration: 1.25, ease: 'expo.inOut' }, 'exit+=0.45')
            .to(ring, { scale: 1, duration: 1.25, ease: 'expo.inOut' }, 'exit+=0.45')
            .to(ring, { autoAlpha: 1, duration: 0.2 }, 'exit+=0.45')
            .to(ring, { autoAlpha: 0, duration: 0.5, ease: 'power2.in' }, 'exit+=1.2');

        const skip = () => {
            if (tl.progress() > 0.02 && tl.progress() < 0.97) {
                tl.timeScale(3.2);
            }
        };

        window.addEventListener('keydown', skip, { once: true });
        window.addEventListener('pointerdown', skip, { once: true });
    });
}

/* -------------------------------------------------------------------------
 * Hero entrance — runs once the loader has handed off.
 * ---------------------------------------------------------------------- */
function runHero() {
    const el = document.querySelector('[data-hero]');
    if (!el) return;

    const q = gsap.utils.selector(el);

    if (reduced) {
        gsap.set(
            q('[data-bg], [data-eyebrow] > span, [data-line] > span, [data-rule], [data-copy], [data-cta] > *, [data-inset], [data-tags] li, [data-marquee]'),
            { clearProps: 'all', autoAlpha: 1, yPercent: 0, xPercent: 0, clipPath: 'none' }
        );
        return;
    }

    const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });

    if (window.innerWidth < 640) {
        tl.timeScale(1.45);
    }

    tl.fromTo(q('[data-bg]'), { clipPath: 'inset(38% 0% 38% 0%)', scale: 1.2 }, { clipPath: 'inset(0% 0% 0% 0%)', scale: 1, duration: 1.5, ease: 'expo.inOut' })
        .fromTo(q('[data-scrim]'), { autoAlpha: 0 }, { autoAlpha: 1, duration: 1.1 }, '-=1.15')
        .fromTo(q('[data-eyebrow] > span'), { yPercent: 130 }, { yPercent: 0, duration: 0.8 }, '-=1.1')
        .fromTo(q('[data-line] > span'), { yPercent: 118, rotate: 2.2 }, { yPercent: 0, rotate: 0, duration: 1.05, stagger: 0.085 }, '-=0.66')
        .fromTo(q('[data-rule]'), { scaleX: 0 }, { scaleX: 1, duration: 0.95, ease: 'expo.inOut' }, '-=0.72')
        .fromTo(q('[data-copy]'), { yPercent: 32, autoAlpha: 0 }, { yPercent: 0, autoAlpha: 1, duration: 0.85 }, '-=0.8')
        .fromTo(q('[data-cta] > *'), { y: 26, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: 0.7, stagger: 0.075 }, '-=0.62')
        .fromTo(q('[data-inset]'), { xPercent: 18, autoAlpha: 0, rotate: 7 }, { xPercent: 0, autoAlpha: 1, rotate: 3.2, duration: 1.15 }, '-=1.15')
        .fromTo(q('[data-tags] li'), { y: 18, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: 0.6, stagger: 0.055 }, '-=0.85')
        .fromTo(q('[data-marquee]'), { autoAlpha: 0, y: 28 }, { autoAlpha: 1, y: 0, duration: 0.8 }, '-=0.7');

    gsap.to(q('[data-bg-inner]'), {
        yPercent: 16,
        scale: 1.12,
        ease: 'none',
        scrollTrigger: { trigger: el, start: 'top top', end: 'bottom top', scrub: 0.6 },
    });

    gsap.to(q('[data-hero-content]'), {
        yPercent: -14,
        autoAlpha: 0.15,
        ease: 'none',
        scrollTrigger: { trigger: el, start: 'top top', end: 'bottom top', scrub: 0.6 },
    });
}

/* -------------------------------------------------------------------------
 * Section entrances, masked headings and parallax layers.
 * ---------------------------------------------------------------------- */
function runReveals() {
    if (reduced) {
        document.querySelectorAll('[data-reveal]').forEach((n) => {
            n.dataset.shown = 'true';
        });
        document.querySelectorAll('[data-split]').forEach((n) => gsap.set(n, { autoAlpha: 1 }));
        return;
    }

    document.querySelectorAll('[data-reveal]').forEach((node) => {
        node.dataset.shown = 'false';
        ScrollTrigger.create({
            trigger: node,
            start: 'top 88%',
            once: true,
            onEnter: () => {
                node.dataset.shown = 'true';
            },
        });
    });

    document.querySelectorAll('[data-split]').forEach((node) => {
        const split = new SplitText(node, {
            type: 'lines',
            linesClass: 'line-mask',
            mask: 'lines',
            autoSplit: true,
        });

        gsap.set(node, { autoAlpha: 1 });
        gsap.from(split.lines, {
            yPercent: 118,
            duration: 1.15,
            stagger: 0.085,
            ease: 'expo.out',
            scrollTrigger: { trigger: node, start: 'top 88%', once: true },
        });
    });

    document.querySelectorAll('[data-parallax]').forEach((node) => {
        const amount = Number(node.dataset.parallax || 10);
        gsap.fromTo(
            node,
            { yPercent: -amount / 2 },
            {
                yPercent: amount / 2,
                ease: 'none',
                scrollTrigger: { trigger: node, start: 'top bottom', end: 'bottom top', scrub: 1 },
            }
        );
    });

    document.querySelectorAll('[data-mask-image]').forEach((node) => {
        const inner = node.querySelector('[data-mask-inner]');
        gsap.timeline({ scrollTrigger: { trigger: node, start: 'top 86%', once: true } })
            .fromTo(node, { clipPath: 'inset(100% 0% 0% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.5, ease: 'expo.inOut' })
            .fromTo(inner, { scale: 1.14 }, { scale: 1, duration: 1.9, ease: 'expo.out' }, '<');
    });
}

/* -------------------------------------------------------------------------
 * Seamless infinite marquee.
 * ---------------------------------------------------------------------- */
function runMarquees() {
    document.querySelectorAll('[data-marquee-track]').forEach((root) => {
        const copies = Array.from(root.querySelectorAll('[data-copy]'));
        if (!copies.length || reduced) return;

        const first = copies[0];
        let width = first.offsetWidth;
        if (!width) return;

        const speed = Number(root.dataset.speed || 40);
        const dir = Number(root.dataset.direction || -1);
        const setters = copies.map((c) => gsap.quickSetter(c, 'x', 'px'));
        const wrap = gsap.utils.wrap(-width, 0);

        let offset = 0;
        let paused = false;

        const tick = (_time, delta) => {
            if (paused) return;
            offset += (width / speed) * (delta / 1000) * dir;
            const x = wrap(offset);
            copies.forEach((_, i) => setters[i](x + i * width));
        };

        copies.forEach((_, i) => setters[i](i * width));
        gsap.ticker.add(tick);

        new IntersectionObserver(([e]) => { paused = !e.isIntersecting; }, { rootMargin: '150px' }).observe(root);
        new ResizeObserver(() => {
            const w = first.offsetWidth;
            if (w) width = w;
        }).observe(first);
    });
}

/* -------------------------------------------------------------------------
 * Full-screen navigation, with focus trapping and scroll lock.
 * ---------------------------------------------------------------------- */
function runNav() {
    const trigger = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');
    if (!trigger || !panel) return;

    const q = gsap.utils.selector(panel);
    let open = false;

    const focusables = () =>
        Array.from(panel.querySelectorAll('a[href], button')).filter((n) => n.getClientRects().length);

    const setOpen = (next) => {
        if (next === open) return;
        open = next;
        trigger.setAttribute('aria-expanded', String(open));

        if (open) {
            lockScroll();
            gsap.set(panel, { autoAlpha: 1, pointerEvents: 'auto' });
            gsap.timeline()
                .fromTo(q('[data-sheet]'), { yPercent: -100 }, { yPercent: 0, duration: 0.95, ease: 'expo.inOut' })
                .fromTo(q('[data-nav-item] > span'), { yPercent: 118 }, { yPercent: 0, duration: 1.05, stagger: 0.062, ease: 'expo.out' }, '-=0.5')
                .fromTo(q('[data-nav-aside] > *'), { autoAlpha: 0, y: 26 }, { autoAlpha: 1, y: 0, duration: 0.9, stagger: 0.075 }, '-=0.75');
            window.setTimeout(() => focusables()[0]?.focus(), 420);
        } else {
            unlockScroll();
            gsap.timeline({ onComplete: () => gsap.set(panel, { autoAlpha: 0, pointerEvents: 'none' }) })
                .to(q('[data-nav-item] > span'), { yPercent: -110, duration: 0.5, stagger: 0.035, ease: 'expo.in' })
                .to(q('[data-sheet]'), { yPercent: -100, duration: 0.8, ease: 'expo.inOut' }, '-=0.24');
            trigger.focus();
        }
    };

    trigger.addEventListener('click', () => setOpen(!open));
    panel.querySelectorAll('[data-nav-close]').forEach((b) => b.addEventListener('click', () => setOpen(false)));

    document.addEventListener('keydown', (e) => {
        if (!open) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            setOpen(false);
            return;
        }

        if (e.key !== 'Tab') return;

        const list = focusables();
        if (!list.length) return;

        const first = list[0];
        const last = list[list.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });
}

/* -------------------------------------------------------------------------
 * Sticky header: compacts on scroll, hides going down, returns going up.
 * ---------------------------------------------------------------------- */
function runHeader() {
    const el = document.querySelector('[data-header]');
    if (!el || reduced) return;

    const plate = el.querySelector('[data-plate]');
    const compact = gsap.timeline({ paused: true }).to(plate, { autoAlpha: 1, duration: 0.4 });

    ScrollTrigger.create({
        start: 64,
        end: 'max',
        onEnter: () => compact.play(),
        onLeaveBack: () => compact.reverse(),
    });

    const hide = gsap.quickTo(el, 'yPercent', { duration: 0.55, ease: 'expo.out' });

    ScrollTrigger.create({
        start: 'top -60%',
        end: 'max',
        onUpdate: (self) => hide(self.direction === 1 ? -110 : 0),
        onLeaveBack: () => hide(0),
    });
}

/* -------------------------------------------------------------------------
 * Menu page: category rail drives the dish list.
 * ---------------------------------------------------------------------- */
function runMenuCourses() {
    const root = document.querySelector('[data-menu]');
    if (!root || reduced) return;

    // Each course stages its own dishes in as it arrives, so a long menu still
    // reads as a sequence rather than a wall.
    root.querySelectorAll('[data-dish-grid]').forEach((grid) => {
        gsap.fromTo(
            grid.querySelectorAll('[data-dish]'),
            { autoAlpha: 0, y: 18 },
            {
                autoAlpha: 1,
                y: 0,
                duration: 0.6,
                stagger: 0.03,
                ease: 'expo.out',
                scrollTrigger: { trigger: grid, start: 'top 88%', once: true },
            }
        );
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Chrome first — these must work whether or not the loader plays.
    runNav();
    runHeader();
    initPageTransitions();

    // Section behaviour.
    runMarquees();
    runReveals();
    runMenuCourses();
    initStatement();
    initCounters();
    initPinnedStory();
    initServicesJourney();
    initProcessTimeline();
    initCollage();
    initSpotlight();
    initTestimonials();
    initGuestPresets();
    initGallery(lockScroll, unlockScroll);

    runLoader().then(runHero);
});
