export interface ServiceCard {
    slug: string;
    title: string;
    subtitle: string | null;
    image: string | null;
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
    hero_title?: string;
    hero_subtitle?: string;
    hero_cta?: string;
    phone?: string;
    phone_raw?: string;
    email?: string;
    address?: string;
    working_hours?: string;
    whatsapp_number?: string;
    whatsapp_greeting?: string;
    whatsapp_teaser?: string;
    /** JSON array of options; each opens WhatsApp with itself as the first message. */
    whatsapp_topics?: string;
    facebook_url?: string;
    linkedin_url?: string;
    seo_title?: string;
    seo_description?: string;
}

export interface NavService {
    title: string;
    url: string;
}

/** Props shared with every Inertia response by HandleInertiaRequests. */
export interface SharedSiteProps {
    settings: SiteSettings;
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
