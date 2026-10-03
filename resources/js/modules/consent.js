/**
 * Cookie consent (UK PECR / GDPR).
 *
 * The only optional cookies are Google's, set by the map embed, so "consent"
 * here means one thing: may the map load by itself. The choice is kept in a
 * first-party cookie for a year; until one is made, nothing optional loads.
 */
const COOKIE = 'mc_cookie_consent';
const ONE_YEAR = 60 * 60 * 24 * 365;

function readChoice() {
    const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE}=([^;]*)`));
    return match ? decodeURIComponent(match[1]) : null;
}

function saveChoice(choice) {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${COOKIE}=${choice}; Max-Age=${ONE_YEAR}; Path=/; SameSite=Lax${secure}`;
}

function loadMap(holder) {
    if (holder.querySelector('iframe')) return;

    const frame = document.createElement('iframe');
    frame.src = holder.dataset.src;
    frame.title = holder.dataset.title || 'Map';
    frame.loading = 'lazy';
    frame.referrerPolicy = 'no-referrer-when-downgrade';
    frame.className = 'h-full w-full';
    frame.style.border = '0';

    holder.replaceChildren(frame);
    holder.classList.remove('px-8');
}

function loadAllMaps() {
    document.querySelectorAll('[data-consent-map]').forEach(loadMap);
}

export function initCookieConsent() {
    const banner = document.querySelector('[data-cookie-banner]');

    const showBanner = (focus = false) => {
        if (!banner) return;
        banner.hidden = false;
        if (focus) banner.querySelector('[data-cookie-choice]')?.focus({ preventScroll: true });
    };

    const choice = readChoice();
    if (choice === 'accepted') {
        loadAllMaps();
    } else if (choice === null) {
        showBanner();
    }

    banner?.querySelectorAll('[data-cookie-choice]').forEach((button) => {
        button.addEventListener('click', () => {
            saveChoice(button.dataset.cookieChoice);
            banner.hidden = true;
            if (button.dataset.cookieChoice === 'accepted') loadAllMaps();
        });
    });

    // "Show map" loads this one map now, without changing the saved choice.
    document.querySelectorAll('[data-consent-map-load]').forEach((button) => {
        button.addEventListener('click', () => loadMap(button.closest('[data-consent-map]')));
    });

    document.querySelectorAll('[data-cookie-settings]').forEach((button) => {
        button.addEventListener('click', () => showBanner(true));
    });
}
