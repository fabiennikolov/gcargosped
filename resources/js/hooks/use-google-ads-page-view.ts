import { useEffect } from 'react';

/**
 * Whether the page view the Google tag sends by itself has already been
 * accounted for.
 *
 * Module-level rather than a ref, because the layout is nested inside each
 * page rather than persisted, so it is mounted afresh on every navigation and
 * an instance flag could never tell the first document load from the second
 * page of the visit.
 */
let documentPageViewCounted = false;

/**
 * Counts the pages an Inertia visit walks through.
 *
 * gtag sends one page view, when the document loads. From there Inertia swaps
 * pages without a reload, so to Google Ads every visitor would look like they
 * arrived and never went anywhere — remarketing lists built on "saw a service
 * page" would stay empty for everyone who did not land on one directly.
 *
 * A page view is not a conversion and is never reported as one; the lead is
 * sent from the inquiry form, once, when the thank-you card appears.
 */
export function useGoogleAdsPageView(id: string | null | undefined, url: string): void {
    useEffect(() => {
        // The first render of the visit is the load gtag already counted.
        const isDocumentLoad = !documentPageViewCounted;
        documentPageViewCounted = true;

        if (isDocumentLoad || !id) {
            return;
        }

        window.gtag?.('event', 'page_view', {
            send_to: id,
            page_location: window.location.href,
            page_title: document.title,
        });
    }, [id, url]);
}
