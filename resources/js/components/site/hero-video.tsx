import { useEffect, useState } from 'react';

/**
 * The looping clip behind the hero. It is 16 MB, so it is added to the page
 * only after everything else has loaded and only where it can be enjoyed: on a
 * wide screen, without a reduced-motion or data-saver preference. A phone
 * keeps the still hero image, and nothing competes with the text and the form
 * for the connection while the page is first painted.
 */
export default function HeroVideo() {
    const [show, setShow] = useState(false);

    useEffect(() => {
        const connection = (navigator as Navigator & { connection?: { saveData?: boolean } }).connection;
        const wide = window.matchMedia('(min-width: 981px)').matches;
        const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!wide || still || connection?.saveData) {
            return;
        }

        if (document.readyState === 'complete') {
            setShow(true);

            return;
        }

        const start = () => setShow(true);
        window.addEventListener('load', start, { once: true });

        return () => window.removeEventListener('load', start);
    }, []);

    if (!show) {
        return null;
    }

    return (
        <video className="hero-video" autoPlay muted loop playsInline preload="metadata">
            <source src="/assets/bridge-truck.mp4" type="video/mp4" />
        </video>
    );
}
