import type { SiteSettings } from '@/types/site';

/**
 * Every tag the root template may have put on the page, as far as the front
 * end needs to talk to them. Each one is missing altogether until it is
 * configured in the admin (Маркетинг), so every call is guarded.
 */
type Tracking = Pick<
    SiteSettings,
    'google_ads_id' | 'google_ads_send_to' | 'ga4_id' | 'gtm_id' | 'meta_pixel_ids' | 'linkedin_partner_id' | 'linkedin_lead_id'
>;

/**
 * Inquiries already reported, so one lead is counted once.
 *
 * Module-level rather than a ref: React can remount the form while the
 * thank-you card is on screen — a re-render in development's strict mode, a
 * navigation back to the same page — and every one of those would otherwise
 * send the lead again. The id is the inquiry's own, so it also rides along as
 * the deduplication key each platform accepts, and a duplicate that slips past
 * this is dropped on their side.
 */
const reportedInquiries = new Set<number>();

/**
 * Tell every configured platform that somebody has asked for an offer.
 *
 * Called only from the inquiry form, once the thank-you card is up — after the
 * inquiry is in the database. Never on a page load: a page load is somebody
 * reading, not a lead.
 */
export function reportLead(tracking: Tracking, inquiryId: number, source: string): void {
    if (reportedInquiries.has(inquiryId)) {
        return;
    }

    reportedInquiries.add(inquiryId);

    const id = String(inquiryId);

    if (tracking.google_ads_send_to) {
        window.gtag?.('event', 'conversion', { send_to: tracking.google_ads_send_to, transaction_id: id });
    }

    if (tracking.ga4_id) {
        window.gtag?.('event', 'generate_lead', { send_to: tracking.ga4_id, inquiry_source: source });
    }

    if (tracking.meta_pixel_ids?.length) {
        window.fbq?.('track', 'Lead', { content_category: source }, { eventID: `inquiry-${id}` });
    }

    if (tracking.linkedin_lead_id) {
        window.lintrk?.('track', { conversion_id: Number(tracking.linkedin_lead_id) });
    }

    // For whatever is built in Tag Manager: one named event to hang a trigger on.
    if (tracking.gtm_id) {
        window.dataLayer?.push({ event: 'inquiry_sent', inquiry_id: id, inquiry_source: source });
    }
}

/**
 * Whether the page view the tags send by themselves has already been
 * accounted for. Module-level because the layout is mounted afresh on every
 * navigation, so an instance flag could never tell the first document load
 * from the second page of the visit.
 */
let documentPageViewCounted = false;

/**
 * Count a page Inertia swapped in without a reload.
 *
 * The tags count a page view when the document loads, and from there Inertia
 * navigates with pushState — so to Google Ads and Meta every visitor would
 * look like they arrived and never went anywhere, and remarketing lists built
 * on "saw a service page" would stay empty for everyone who did not land on one
 * directly. Analytics 4 is left out: its enhanced measurement already follows
 * history changes, and sending one here too would count every page twice.
 */
export function reportPageView(tracking: Tracking): void {
    // The first render of the visit is the load the tags already counted.
    if (!documentPageViewCounted) {
        documentPageViewCounted = true;

        return;
    }

    if (tracking.google_ads_id) {
        window.gtag?.('event', 'page_view', {
            send_to: tracking.google_ads_id,
            page_location: window.location.href,
            page_title: document.title,
        });
    }

    if (tracking.meta_pixel_ids?.length) {
        window.fbq?.('track', 'PageView');
    }
}
