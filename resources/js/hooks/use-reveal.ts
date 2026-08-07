import { useEffect } from 'react';

/**
 * The lifted stylesheet hides every `.reveal` element (`opacity:0`) and only
 * shows it once the class `in` is added. On the original static site an
 * IntersectionObserver did that; without it the services, steps, partners and
 * cards are all present in the DOM but invisible.
 *
 * Same observer settings as the original, and it re-runs on every Inertia
 * navigation because the new page's nodes have never been observed.
 */
export function useReveal(dependency: unknown): void {
    useEffect(() => {
        const targets = Array.from(document.querySelectorAll<HTMLElement>('.reveal:not(.in)'));

        if (targets.length === 0) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            targets.forEach((el) => el.classList.add('in'));

            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1, rootMargin: '0px 0px -40px 0px' },
        );

        targets.forEach((el) => observer.observe(el));

        return () => observer.disconnect();
    }, [dependency]);
}
