import gsap from 'gsap';

const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* -------------------------------------------------------------------------
 * Gallery filtering + full-screen lightbox.
 *
 * Keyboard: arrows move, Home/End jump, Escape closes, Tab is trapped inside.
 * Touch: swipe left/right. Focus returns to the tile that opened it.
 * ---------------------------------------------------------------------- */
export function initGallery(lockScroll, unlockScroll) {
    const root = document.querySelector('[data-gallery]');
    if (!root) return;

    const tiles = Array.from(root.querySelectorAll('[data-tile]'));
    const filters = Array.from(root.querySelectorAll('[data-filter]'));
    const countEl = root.querySelector('[data-gallery-count]');

    /* ------------------------------ Filtering ---------------------------- */
    const applyFilter = (category) => {
        filters.forEach((f) => f.setAttribute('aria-selected', String(f.dataset.filter === category)));

        let shown = 0;
        tiles.forEach((tile) => {
            const match = category === 'All' || tile.dataset.category === category;
            if (match) shown++;

            if (reduced()) {
                tile.hidden = !match;
                return;
            }

            gsap.to(tile, {
                autoAlpha: match ? 1 : 0,
                scale: match ? 1 : 0.94,
                duration: 0.45,
                ease: 'expo.out',
                onStart: () => { if (match) tile.hidden = false; },
                onComplete: () => { if (!match) tile.hidden = true; },
            });
        });

        if (countEl) countEl.textContent = String(shown);
    };

    filters.forEach((f) => f.addEventListener('click', () => applyFilter(f.dataset.filter)));

    /* ------------------------------ Lightbox ----------------------------- */
    const box = document.querySelector('[data-lightbox]');
    if (!box) return;

    const imgEl = box.querySelector('[data-lightbox-image]');
    const capEl = box.querySelector('[data-lightbox-caption]');
    const catEl = box.querySelector('[data-lightbox-category]');
    const posEl = box.querySelector('[data-lightbox-position]');
    const totalEl = box.querySelector('[data-lightbox-total]');
    const strip = box.querySelector('[data-lightbox-strip]');

    const items = tiles.map((t) => ({
        src: t.dataset.full,
        thumb: t.dataset.thumb || t.dataset.full,
        caption: t.dataset.caption,
        category: t.dataset.category,
        alt: t.dataset.caption,
    }));

    let index = -1;
    let opener = null;

    if (totalEl) totalEl.textContent = String(items.length).padStart(2, '0');

    // Thumbnail strip
    if (strip) {
        items.forEach((item, i) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'relative h-12 w-16 shrink-0 overflow-hidden opacity-35 transition-all duration-500 hover:opacity-70';
            b.setAttribute('aria-label', `View ${item.caption}`);
            b.innerHTML = `<img src="${item.thumb}" alt="" loading="lazy" class="h-full w-full object-cover">`;
            b.addEventListener('click', () => show(i));
            strip.appendChild(b);
        });
    }

    const focusables = () =>
        Array.from(box.querySelectorAll('button')).filter((n) => n.getClientRects().length);

    const show = (i) => {
        index = (i + items.length) % items.length;
        const item = items[index];

        if (imgEl) {
            imgEl.src = item.src;
            imgEl.alt = item.alt;
        }
        if (capEl) capEl.textContent = item.caption;
        if (catEl) catEl.textContent = item.category;
        if (posEl) posEl.textContent = String(index + 1).padStart(2, '0');

        if (strip) {
            Array.from(strip.children).forEach((child, n) => {
                child.classList.toggle('opacity-100', n === index);
                child.classList.toggle('ring-1', n === index);
                child.classList.toggle('ring-gold', n === index);
                child.classList.toggle('opacity-35', n !== index);
            });
            strip.children[index]?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
        }

        if (!reduced() && imgEl) {
            gsap.fromTo(imgEl, { autoAlpha: 0, scale: 0.97 }, { autoAlpha: 1, scale: 1, duration: 0.45, ease: 'expo.out' });
        }
    };

    const open = (i, trigger) => {
        opener = trigger;
        box.hidden = false;
        lockScroll();
        show(i);
        gsap.fromTo(box, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.35 });
        window.setTimeout(() => focusables()[0]?.focus(), 60);
    };

    const close = () => {
        gsap.to(box, {
            autoAlpha: 0,
            duration: 0.3,
            onComplete: () => {
                box.hidden = true;
                unlockScroll();
                opener?.focus();
            },
        });
    };

    tiles.forEach((tile, i) => tile.addEventListener('click', () => open(i, tile)));
    box.querySelectorAll('[data-lightbox-close]').forEach((b) => b.addEventListener('click', close));
    box.querySelector('[data-lightbox-prev]')?.addEventListener('click', () => show(index - 1));
    box.querySelector('[data-lightbox-next]')?.addEventListener('click', () => show(index + 1));

    document.addEventListener('keydown', (e) => {
        if (box.hidden) return;

        switch (e.key) {
            case 'Escape': e.preventDefault(); close(); break;
            case 'ArrowRight': e.preventDefault(); show(index + 1); break;
            case 'ArrowLeft': e.preventDefault(); show(index - 1); break;
            case 'Home': e.preventDefault(); show(0); break;
            case 'End': e.preventDefault(); show(items.length - 1); break;
            case 'Tab': {
                const list = focusables();
                if (!list.length) return;
                const first = list[0];
                const last = list[list.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                break;
            }
        }
    });

    // Swipe on touch.
    let startX = null;
    box.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', (e) => {
        if (startX === null) return;
        const dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 60) show(dx < 0 ? index + 1 : index - 1);
        startX = null;
    }, { passive: true });
}

/* -------------------------------------------------------------------------
 * Testimonial carousel: auto-advances, pauses on interaction, drag + keys.
 * ---------------------------------------------------------------------- */
export function initTestimonials() {
    const root = document.querySelector('[data-testimonials]');
    if (!root) return;

    const slides = Array.from(root.querySelectorAll('[data-testimonial]'));
    const dots = Array.from(root.querySelectorAll('[data-testimonial-dot]'));
    if (slides.length < 2) return;

    let index = 0;
    let paused = false;

    const show = (i) => {
        index = (i + slides.length) % slides.length;

        slides.forEach((s, n) => {
            if (reduced()) {
                s.style.opacity = n === index ? '1' : '0';
                s.style.pointerEvents = n === index ? 'auto' : 'none';
                return;
            }
            gsap.to(s, {
                autoAlpha: n === index ? 1 : 0,
                y: n === index ? 0 : 24,
                duration: 0.7,
                ease: 'expo.out',
                pointerEvents: n === index ? 'auto' : 'none',
            });
        });

        dots.forEach((d, n) => {
            d.setAttribute('aria-current', n === index ? 'true' : 'false');
            const bar = d.querySelector('span');
            if (bar) {
                bar.style.width = n === index ? '2rem' : '0.75rem';
                bar.style.backgroundColor = n === index ? 'var(--color-gold)' : 'rgba(244,238,226,0.35)';
            }
        });
    };

    root.querySelector('[data-testimonial-prev]')?.addEventListener('click', () => show(index - 1));
    root.querySelector('[data-testimonial-next]')?.addEventListener('click', () => show(index + 1));
    dots.forEach((d, i) => d.addEventListener('click', () => show(i)));

    root.addEventListener('mouseenter', () => { paused = true; });
    root.addEventListener('mouseleave', () => { paused = false; });
    root.addEventListener('focusin', () => { paused = true; });
    root.addEventListener('focusout', () => { paused = false; });

    root.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight') show(index + 1);
        if (e.key === 'ArrowLeft') show(index - 1);
    });

    // Swipe
    let startX = null;
    root.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; }, { passive: true });
    root.addEventListener('touchend', (e) => {
        if (startX === null) return;
        const dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 60) show(dx < 0 ? index + 1 : index - 1);
        startX = null;
    }, { passive: true });

    if (!reduced()) {
        window.setInterval(() => { if (!paused) show(index + 1); }, 7000);
    }

    show(0);
}

/* -------------------------------------------------------------------------
 * Quick-pick guest numbers on the enquiry form. Purely a shortcut: the
 * number field works on its own when the browser has no JS.
 * ---------------------------------------------------------------------- */
export function initGuestPresets() {
    const form = document.querySelector('[data-enquiry-form]');
    if (!form) return;

    const guests = form.querySelector('#guests');
    const presets = Array.from(form.querySelectorAll('[data-guest-preset]'));
    if (!guests || !presets.length) return;

    const markActive = () => {
        presets.forEach((b) => {
            const on = b.dataset.guestPreset === guests.value;
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            b.classList.toggle('border-gold', on);
            b.classList.toggle('text-gold', on);
            b.classList.toggle('border-cream/18', !on);
            b.classList.toggle('text-cream/60', !on);
        });
    };

    presets.forEach((button) => {
        button.addEventListener('click', () => {
            guests.value = button.dataset.guestPreset;
            guests.dispatchEvent(new Event('input', { bubbles: true }));
            markActive();
        });
    });

    guests.addEventListener('input', markActive);
    markActive();
}
