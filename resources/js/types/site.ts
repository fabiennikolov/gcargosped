export interface ServiceCard {
    slug: string;
    title: string;
    subtitle: string | null;
    image: string | null;
    /** Small copy for listing cards; falls back to `image` when absent. */
    imageThumb?: string | null;
    /** Written in the admin, never drawn on the page — falls back to the title. */
    imageAlt?: string | null;
    icon: string | null;
    isMain: boolean;
    url: string;
}

export interface ServiceDetail {
    slug: string;
    title: string;
    subtitle: string | null;
    body: string | null;
    image: string | null;
    imageAlt?: string | null;
    isMain: boolean;
    url: string;
}

export interface PostCard {
    slug: string;
    title: string;
    excerpt: string | null;
    coverImage: string | null;
    category?: string | null;
    readMinutes: number;
    publishedAt: string | null;
    url: string;
}

export interface PostDetail extends Omit<PostCard, 'url'> {
    /** Sanitised HTML. */
    body: string | null;
}

export interface Partner {
    name: string;
    logo: string | null;
    url: string | null;
}

export interface PageMeta {
    title: string | null;
    description: string | null;
}

/** Everything the admin can edit under "Настройки на сайта". */
export interface SiteSettings {
    site_name?: string;
    /** Stored path of the uploaded logo; use logo_url to render it. */
    logo_path?: string;
    /** logo_path resolved to a browser URL by HandleInertiaRequests. */
    logo_url?: string | null;
    hero_title?: string;
    /** Percent of the designed size, e.g. "90". Empty = 100. */
    hero_title_size?: string;
    hero_subtitle?: string;
    hero_cta?: string;
    /** JSON array of {value, label} rows for the dark band on the home page. */
    stats?: string;
    phone?: string;
    phone_raw?: string;
    email?: string;
    address?: string;
    working_hours?: string;
    /** The line under the logo in the footer. */
    footer_tagline?: string;
    whatsapp_number?: string;
    whatsapp_greeting?: string;
    whatsapp_teaser?: string;
    /** JSON array of options; each opens WhatsApp with itself as the first message. */
    whatsapp_topics?: string;
    inquiry_title?: string;
    inquiry_subtitle?: string;
    /** Shown after a form is sent; the server sends it through as flash.inquiry.message. */
    inquiry_success?: string;
    facebook_url?: string;
    linkedin_url?: string;
    seo_title?: string;
    seo_description?: string;
    /** Google Ads tag ID (AW-…), already checked server-side; null until configured. */
    google_ads_id?: string | null;
    /** The conversion label, as typed in the admin. Use google_ads_send_to instead. */
    google_ads_label?: string;
    /** "AW-…/label" — what a conversion names as its destination. Null until both halves are set. */
    google_ads_send_to?: string | null;
    /** Google Analytics 4 measurement ID (G-…), checked server-side; null until configured. */
    ga4_id?: string | null;
    /** Google Tag Manager container (GTM-…); null until configured. */
    gtm_id?: string | null;
    /** Meta pixel IDs, checked server-side; empty until configured. */
    meta_pixel_ids?: string[];
    /** LinkedIn Insight Tag partner ID; null until configured. */
    linkedin_partner_id?: string | null;
    /** LinkedIn conversion reported for a sent inquiry; null unless the partner ID is set too. */
    linkedin_lead_id?: string | null;
    /** True when any tracking tag is configured, so the cookie banner has to ask. */
    tracking_consent_required?: boolean;
    /** The cookie banner's text; empty falls back to the wording in the component. */
    consent_text?: string;
    /** Privacy policy linked from the cookie banner; http(s) only, checked server-side. */
    privacy_url?: string | null;
}

export interface NavService {
    title: string;
    url: string;
}

/** Props shared with every Inertia response by HandleInertiaRequests. */
/** One field of a public inquiry form, as arranged in the admin (Форми). */
export interface FormField {
    /** Column name for a standard field, `x<id>` (posted inside `extra`) for the admin's own. */
    key: string;
    builtin: boolean;
    type: 'text' | 'tel' | 'email' | 'textarea' | 'number' | 'date' | 'select' | 'checkbox';
    label: string;
    placeholder: string;
    help: string;
    required: boolean;
    half: boolean;
    options: string[];
}

export interface FormConfig {
    fields: FormField[];
    button: string | null;
}

export interface SharedSiteProps {
    settings: SiteSettings;
    forms: { lead: FormConfig; offer: FormConfig };
    /** The address this page declares as its own — same value as <link rel="canonical">. */
    canonical?: string;
    nav: { mainServices: NavService[] };
    flash: { inquiry?: { ok: boolean; id: number; message: string } };
    /** Shared for the SSR route() helper; also the only origin available server-side. */
    ziggy?: { url: string; location: string };
    [key: string]: unknown;
}

/** Laravel paginator shape, as returned through an API resource collection. */
export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: { current_page: number; last_page: number; total: number };
    current_page?: number;
    last_page?: number;
    total?: number;
}

declare global {
    interface Window {
        /**
         * Defined synchronously by the Google tag in the root template, before
         * React mounts, and missing altogether until an Ads ID is configured —
         * hence every call site has to guard. Calls made before the library
         * itself has loaded are queued on dataLayer, so they are not lost.
         */
        gtag?: (command: 'config' | 'event' | 'js' | 'consent' | 'set', target: string | Date, params?: Record<string, unknown> | boolean) => void;
        /** Meta Pixel; defined by the root template only when a pixel is configured. */
        fbq?: (command: 'track' | 'init' | 'consent', event: string, params?: Record<string, unknown>, options?: { eventID?: string }) => void;
        /** LinkedIn Insight Tag; defined only when a partner ID is configured. */
        lintrk?: (command: 'track', params: Record<string, unknown>) => void;
        /** Google's queue, shared by gtag.js and Tag Manager. */
        dataLayer?: unknown[];
    }
}
