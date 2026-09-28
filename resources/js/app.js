/*
 * Operations dashboard behaviour.
 *
 * Deliberately small and dependency-free: this runs on kitchen phones over
 * patchy wifi, so every interaction here degrades to a working page without it.
 */

/* ------------------------------------------------------------------ theme */
function initTheme() {
    const root = document.documentElement;

    const paint = (theme) => {
        root.dataset.theme = theme;

        document.querySelectorAll('[data-theme-icon]').forEach((icon) => {
            const wantsDark = icon.dataset.themeIcon === 'dark';
            icon.classList.toggle('hidden', wantsDark ? theme === 'dark' : theme !== 'dark');
        });
    };

    paint(root.dataset.theme || 'light');

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
            paint(next);
            try {
                localStorage.setItem('mc-theme', next);
            } catch {
                /* private mode — the choice simply will not persist */
            }
        });
    });
}

/* -------------------------------------------------------------- mobile nav */
function initNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-nav]');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', () => {
        const open = nav.classList.toggle('hidden');
        toggle.setAttribute('aria-expanded', String(!open));
    });
}

/* ---------------------------------------------------------------- confirms */
function initConfirms() {
    const dialog = document.getElementById('confirm-dialog');
    const message = document.getElementById('confirm-message');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    document.addEventListener(
        'click',
        (event) => {
            const trigger = event.target.closest('[data-confirm]');
            if (!trigger) return;

            // Already confirmed on a previous pass — let it through.
            if (trigger.dataset.confirmed === 'yes') {
                delete trigger.dataset.confirmed;
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            message.textContent = trigger.dataset.confirm;
            dialog.returnValue = 'cancel';
            dialog.showModal();

            dialog.addEventListener(
                'close',
                () => {
                    if (dialog.returnValue !== 'confirm') return;
                    trigger.dataset.confirmed = 'yes';
                    trigger.click();
                },
                { once: true }
            );
        },
        true
    );
}

/* --------------------------------------------------------- flash dismissal */
function initDismiss() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-dismiss]');
        if (button) button.closest('[role="status"], [role="alert"]')?.remove();
    });
}

/* ------------------------------------------------------- submit protection */
function initSubmitGuard() {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            // A second tap on a slow connection must not create a second record.
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                if (button.dataset.noGuard !== undefined) return;

                window.setTimeout(() => {
                    button.disabled = true;
                    button.dataset.busy = 'true';
                }, 0);
            });
        });
    });

    // Coming back via the bfcache must not leave buttons stuck.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('button[data-busy="true"]').forEach((button) => {
            button.disabled = false;
            delete button.dataset.busy;
        });
    });
}

/* ------------------------------------------------- live totals on forms */
function initLineTotals() {
    const update = () => {
        document.querySelectorAll('[data-line-total]').forEach((output) => {
            const scope = output.closest('[data-line-scope]') || document;
            const quantity = parseFloat(scope.querySelector('[data-line-qty]')?.value ?? '0') || 0;
            const price = parseFloat(scope.querySelector('[data-line-price]')?.value ?? '0') || 0;

            output.textContent = '£' + (quantity * price).toFixed(2);
        });
    };

    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-line-qty], [data-line-price]')) update();
    });

    update();
}

/* ------------------------------------------------------- clickable rows */
function initRowLinks() {
    document.addEventListener('click', (event) => {
        const row = event.target.closest('[data-row-href]');
        if (!row) return;

        // Never swallow a real control inside the row, or a text selection.
        if (event.target.closest('a, button, input, select, textarea, label, form')) return;
        if ((window.getSelection()?.toString() ?? '').length > 0) return;

        // Middle-click and modifier-click keep their usual meaning.
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
            window.open(row.dataset.rowHref, '_blank', 'noopener');
            return;
        }

        window.location.href = row.dataset.rowHref;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initRowLinks();
    initTheme();
    initNav();
    initConfirms();
    initDismiss();
    initSubmitGuard();
    initLineTotals();
});
