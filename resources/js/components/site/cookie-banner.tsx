import { type Consent, OPEN_CONSENT_EVENT, setConsent, storedConsent } from '@/lib/consent';
import { useEffect, useState } from 'react';

/** What the banner says until the text is edited in the admin (Маркетинг). */
const DEFAULT_TEXT = 'Използваме бисквитки, за да измерваме посещенията и рекламите си. Можете да откажете — сайтът работи и без тях.';

interface Props {
    /** False when no tracking tag is configured: nothing to ask about. */
    required?: boolean;
    text?: string | null;
    privacyUrl?: string | null;
}

/**
 * Asks once whether the tracking tags may use cookies, and again whenever the
 * visitor opens it from the footer.
 *
 * Refusing is as easy as accepting — two equal buttons, no pre-ticked boxes,
 * no "continue browsing means yes" — because that is what GDPR asks for, and
 * closing the banner without answering is not an answer, so there is no ×.
 *
 * Rendered only after mounting: the answer lives in the visitor's browser, so
 * the server cannot know whether to draw it, and drawing it there would make
 * the hydrated page differ from the SSR markup.
 */
export default function CookieBanner({ required, text, privacyUrl }: Props) {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (!required) {
            return;
        }

        setOpen(storedConsent() === null);

        const reopen = () => setOpen(true);
        window.addEventListener(OPEN_CONSENT_EVENT, reopen);

        return () => window.removeEventListener(OPEN_CONSENT_EVENT, reopen);
    }, [required]);

    if (!open) {
        return null;
    }

    const answer = (consent: Consent) => {
        setConsent(consent);
        setOpen(false);
    };

    return (
        <div className="cookie-banner glass" role="dialog" aria-live="polite" aria-label="Бисквитки">
            <p>
                {text || DEFAULT_TEXT}{' '}
                {privacyUrl && (
                    <a href={privacyUrl} target="_blank" rel="noopener noreferrer">
                        Научете повече
                    </a>
                )}
            </p>
            <div className="cookie-actions">
                <button type="button" className="btn btn-glass" onClick={() => answer('denied')}>
                    Отказвам
                </button>
                <button type="button" className="btn btn-accent" onClick={() => answer('granted')}>
                    Приемам
                </button>
            </div>
        </div>
    );
}
