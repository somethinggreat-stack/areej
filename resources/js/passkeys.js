/*
 * Passkeys: sign in with Face ID, a fingerprint or the phone's screen lock.
 *
 * Talks to the laravel/passkeys routes directly with the browser's own
 * WebAuthn API, so there is no extra package. Buttons stay hidden in browsers
 * that cannot do passkeys, so nobody is offered something that will not work.
 */

const supported = () => typeof window.PublicKeyCredential === 'function' && !!navigator.credentials;

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const toBuffer = (value) => {
    const base64 = value.replace(/-/g, '+').replace(/_/g, '/').padEnd(Math.ceil(value.length / 4) * 4, '=');
    return Uint8Array.from(atob(base64), (c) => c.charCodeAt(0)).buffer;
};

const toBase64Url = (buffer) => btoa(String.fromCharCode(...new Uint8Array(buffer)))
    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

const creationOptions = (json) => {
    if (typeof PublicKeyCredential.parseCreationOptionsFromJSON === 'function') {
        return PublicKeyCredential.parseCreationOptionsFromJSON(json);
    }

    return {
        ...json,
        challenge: toBuffer(json.challenge),
        user: { ...json.user, id: toBuffer(json.user.id) },
        excludeCredentials: (json.excludeCredentials ?? []).map((c) => ({ ...c, id: toBuffer(c.id) })),
    };
};

const requestOptions = (json) => {
    if (typeof PublicKeyCredential.parseRequestOptionsFromJSON === 'function') {
        return PublicKeyCredential.parseRequestOptionsFromJSON(json);
    }

    return {
        ...json,
        challenge: toBuffer(json.challenge),
        allowCredentials: (json.allowCredentials ?? []).map((c) => ({ ...c, id: toBuffer(c.id) })),
    };
};

const credentialJson = (credential) => {
    if (typeof credential.toJSON === 'function') {
        return credential.toJSON();
    }

    const r = credential.response;
    const response = { clientDataJSON: toBase64Url(r.clientDataJSON) };

    if (r.attestationObject) {
        response.attestationObject = toBase64Url(r.attestationObject);
        response.transports = r.getTransports?.() ?? [];
    } else {
        response.authenticatorData = toBase64Url(r.authenticatorData);
        response.signature = toBase64Url(r.signature);
        response.userHandle = r.userHandle ? toBase64Url(r.userHandle) : null;
    }

    return {
        id: credential.id,
        rawId: toBase64Url(credential.rawId),
        type: credential.type,
        response,
        clientExtensionResults: credential.getClientExtensionResults(),
        authenticatorAttachment: credential.authenticatorAttachment ?? null,
    };
};

async function send(url, method = 'GET', body = null) {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : null,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const first = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(first || data.message || 'Something went wrong. Please try again.');
    }

    return data;
}

const showError = (message) => {
    const box = document.querySelector('[data-passkey-error]');
    if (!box) return;
    box.textContent = message;
    box.classList.remove('hidden');
};

const cancelled = (error) => error?.name === 'NotAllowedError' || error?.name === 'AbortError';

function initLogin() {
    const button = document.querySelector('[data-passkey-login]');
    if (!button || !supported()) return;

    document.querySelector('[data-passkey-login-wrap]')?.classList.remove('hidden');

    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            const { options } = await send(button.dataset.optionsUrl);
            const credential = await navigator.credentials.get({ publicKey: requestOptions(options) });
            const remember = document.querySelector('[data-passkey-remember]')?.checked ?? false;
            const result = await send(button.dataset.verifyUrl, 'POST', { credential: credentialJson(credential), remember });
            window.location.assign(result.redirect || '/dashboard');
        } catch (error) {
            if (!cancelled(error)) showError(error.message);
        } finally {
            button.disabled = false;
        }
    });
}

function initRegister() {
    const form = document.querySelector('[data-passkey-register]');
    if (!form) return;

    if (!supported()) {
        form.querySelector('[data-passkey-unsupported]')?.classList.remove('hidden');
        form.querySelector('button')?.setAttribute('disabled', 'disabled');
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button');
        button.disabled = true;
        try {
            const { options } = await send(form.dataset.optionsUrl);
            const credential = await navigator.credentials.create({ publicKey: creationOptions(options) });
            const name = form.querySelector('[name="name"]').value.trim() || 'Passkey';
            await send(form.action, 'POST', { name, credential: credentialJson(credential) });
            window.location.reload();
        } catch (error) {
            if (!cancelled(error)) showError(error.message);
        } finally {
            button.disabled = false;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initLogin();
    initRegister();
});
