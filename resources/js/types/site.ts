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
}

export interface NavService {
    title: string;
    url: string;
}

/** Props shared with every Inertia response by HandleInertiaRequests. */
export interface SharedSiteProps {
    settings: SiteSettings;
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
        gtag?: (command: 'config' | 'event' | 'js', target: string | Date, params?: Record<string, unknown>) => void;
    }
}
