import { useEffect, useRef, useState } from 'react';

const DEFAULT_GREETING = 'Здравейте! С какво можем да помогнем?';
const DEFAULT_TEASER = 'Имате въпрос? Пишете ни.';
const DEFAULT_TOPICS = ['Оферта за превоз', 'Проследяване на товар', 'Въпрос за документи', 'Друго'];

/** Session, not local: a returning visitor next week is worth reminding again. */
const DISMISSED_KEY = 'wa-teaser-dismissed';

interface Props {
    /** Empty or missing hides the button entirely. */
    number?: string;
    greeting?: string;
    /** Bubble shown beside the button until it is opened or dismissed. */
    teaser?: string;
    /** One topic per line, as entered in the admin. */
    topics?: string;
    /** Current path, sent with the click so we know which page raised the question. */
    page: string;
}

/**
 * The admin stores the options as a JSON array. Newline-separated text is still
 * accepted because that is how the field was stored before it became a
 * repeater, and a site that has not re-saved its settings must not lose its
 * menu.
 */
function parseTopics(raw?: string): string[] {
    const value = (raw ?? '').trim();
    if (!value) return [];

    const clean = (items: unknown[]) => items.map((item) => String(item).trim()).filter(Boolean);

    try {
        const parsed: unknown = JSON.parse(value);
        if (Array.isArray(parsed)) return clean(parsed);
    } catch {
        // Not JSON — fall through to the legacy line-separated form.
    }

    return clean(value.split('\n'));
}

/**
 * wa.me wants bare international digits, but the number is typed by hand in the
 * admin and "0877 415 141" is how a Bulgarian writes their own phone. Left as
 * typed it opens a chat with a number that does not exist, and nothing in the
 * UI says so — hence the national and 00-prefixed forms are accepted too.
 */
function toWaNumber(raw?: string): string {
    const digits = (raw ?? '').replace(/\D/g, '');

    if (digits.startsWith('00')) return digits.slice(2);
    if (digits.startsWith('0')) return `359${digits.slice(1)}`;

    return digits;
}

function WhatsappGlyph() {
    return (
        <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden="true">
            <path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.48s1.06 2.87 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.75-.72 2-1.41.25-.69.25-1.28.17-1.4-.07-.13-.27-.2-.57-.35z" />
            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.86 9.86 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm0 18.13h-.01a8.2 8.2 0 0 1-4.18-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.17 8.17 0 0 1-1.25-4.36c0-4.54 3.7-8.23 8.24-8.23a8.18 8.18 0 0 1 5.82 2.42 8.18 8.18 0 0 1 2.41 5.82c0 4.54-3.7 8.23-8.25 8.23z" />
        </svg>
    );
}

/**
 * Floating click-to-chat button.
 *
 * Deliberately not a bot: every option opens WhatsApp with its own opening
 * message, so the office sees what the visitor wants before saying hello and
 * still answers from the phone they already use. The click is recorded first —
 * the conversation continues where we cannot see it, so the tap is the only
 * thing we ever get to measure.
 *
 * A corner button that never moves gets read as decoration, so it asks up
 * front: a slow pulse and a teaser bubble that is already open on arrival. Both
 * stop for good on the first interaction, and there is no unread badge — a fake
 * "1" earns a tap by lying about a message that does not exist, and the visitor
 * finds out the moment the chat opens empty.
 */
export default function WhatsappButton({ number, greeting, teaser, topics, page }: Props) {
    const [open, setOpen] = useState(false);
    const [teaserShown, setTeaserShown] = useState(false);
    const [engaged, setEngaged] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);

    const digits = toWaNumber(number);
    const teaserText = teaser?.trim() || DEFAULT_TEASER;

    /*
     * The bubble is open from the start and stays until it is closed — set in an
     * effect rather than as initial state so a visitor who already dismissed it
     * never sees it flash on the next page.
     */
    useEffect(() => {
        if (!digits || engaged) return;

        try {
            if (sessionStorage.getItem(DISMISSED_KEY)) return;
        } catch {
            // Storage refused (private browsing): show it, as for a new visitor.
        }

        setTeaserShown(true);
    }, [digits, engaged]);

    // Close on outside click and on Escape — a panel that traps the visitor in
    // the corner of every page is worse than no panel.
    useEffect(() => {
        if (!open) return;

        const onPointerDown = (event: MouseEvent) => {
            if (!rootRef.current?.contains(event.target as Node)) setOpen(false);
        };
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setOpen(false);
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    if (!digits) return null;

    const options = parseTopics(topics);
    const menu = options.length > 0 ? options : DEFAULT_TOPICS;

    /** Any interaction retires the pulse and the bubble for the rest of the session. */
    const settle = () => {
        setTeaserShown(false);
        setEngaged(true);
        try {
            sessionStorage.setItem(DISMISSED_KEY, '1');
        } catch {
            // Private browsing can refuse storage; the bubble simply reappears.
        }
    };

    const openChat = (topic: string) => {
        /* Fire-and-forget: the browser is already navigating to wa.me, so this
           must not be awaited. sendBeacon survives the page going away; fetch
           with keepalive is the fallback where it is missing. */
        const body = JSON.stringify({ topic, page });
        try {
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/track/whatsapp', new Blob([body], { type: 'application/json' }));
            } else {
                void fetch('/track/whatsapp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body,
                    keepalive: true,
                });
            }
        } catch {
            // Never let analytics stand between a visitor and the chat.
        }

        window.open(`https://wa.me/${digits}?text=${encodeURIComponent(topic)}`, '_blank', 'noopener');
        setOpen(false);
    };

    const toggle = () => {
        settle();
        setOpen((value) => !value);
    };

    const dockClass = ['wa-dock', open ? 'open' : '', !open && !engaged ? 'nudge' : ''].filter(Boolean).join(' ');

    return (
        <div className={dockClass} ref={rootRef}>
            {teaserShown && !open && (
                <div className="wa-teaser">
                    <button type="button" className="wa-teaser-body" onClick={toggle}>
                        {teaserText}
                    </button>
                    <button
                        type="button"
                        className="wa-teaser-x"
                        aria-label="Скрий"
                        onClick={(event) => {
                            event.stopPropagation();
                            settle();
                        }}
                    >
                        ×
                    </button>
                </div>
            )}

            <div className="wa-panel" role="dialog" aria-label="Пишете ни в WhatsApp" hidden={!open}>
                {/* Clicking outside and Escape both close this, but neither is
                    discoverable — a visible × is what most people look for. */}
                <button type="button" className="wa-panel-x" aria-label="Затвори" onClick={() => setOpen(false)}>
                    ×
                </button>

                <p className="wa-greet">{greeting?.trim() || DEFAULT_GREETING}</p>
                <ul>
                    {menu.map((topic) => (
                        <li key={topic}>
                            <button type="button" onClick={() => openChat(topic)}>
                                {topic}
                            </button>
                        </li>
                    ))}
                </ul>
            </div>

            <button
                type="button"
                className="wa-fab"
                aria-label={open ? 'Затвори WhatsApp менюто' : 'Пишете ни в WhatsApp'}
                aria-expanded={open}
                onClick={toggle}
            >
                <WhatsappGlyph />
            </button>
        </div>
    );
}
