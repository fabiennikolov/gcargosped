/**
 * The visitor's answer to the cookie banner.
 *
 * Stored in localStorage rather than a cookie: the server never needs it, and
 * the root template reads it from there before any tag is set up (see
 * resources/views/app.blade.php). Both sides must agree on the key and the
 * shape, so change them together. An answer older than STORAGE_DAYS is
 * treated as no answer and the banner asks again.
 */
const STORAGE_KEY = 'gcs-consent';
const STORAGE_VERSION = 1;
const STORAGE_DAYS = 180;

/** Fired to show the banner again, from the "Бисквитки" link in the footer. */
export const OPEN_CONSENT_EVENT = 'gcs:open-consent';

export type Consent = 'granted' | 'denied';

declare global {
    interface Window {
        /** Set by the root template from storage; null until the visitor answers. */
        tagConsent?: Consent | null;
        /** Fetches the libraries held back for consent (Meta, LinkedIn). */
        releaseGatedTags?: () => void;
    }
}

export function storedConsent(): Consent | null {
    try {
        const stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? 'null');

        if (stored?.v === STORAGE_VERSION && Date.now() - stored.t < STORAGE_DAYS * 864e5) {
            return stored.granted === true ? 'granted' : 'denied';
        }
    } catch {
        // Storage blocked or garbled: no answer, so ask.
    }

    return null;
}

/**
 * Record the answer and apply it to the tags already on the page, without a
 * reload: Google is told through Consent Mode, and the libraries held back
 * for consent are fetched. Taking consent back cannot unload a library that
 * is already running, so Meta is told to stop as well; LinkedIn has no such
 * switch and is simply not loaded on the next page load.
 */
export function setConsent(consent: Consent): void {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ v: STORAGE_VERSION, t: Date.now(), granted: consent === 'granted' }));
    } catch {
        // Private mode with storage off: the answer holds for this page only.
    }

    window.tagConsent = consent;

    window.gtag?.('consent', 'update', {
        ad_storage: consent,
        ad_user_data: consent,
        ad_personalization: consent,
        analytics_storage: consent,
    });
    window.gtag?.('set', 'ads_data_redaction', consent === 'denied');

    // A trigger for anything built in Tag Manager that is not a Google tag.
    window.dataLayer?.push({ event: 'consent_update', consent });

    if (consent === 'granted') {
        window.fbq?.('consent', 'grant');
        window.releaseGatedTags?.();
    } else {
        window.fbq?.('consent', 'revoke');
    }
}

export function openConsent(): void {
    window.dispatchEvent(new Event(OPEN_CONSENT_EVENT));
}
