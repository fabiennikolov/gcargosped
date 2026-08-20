import Brand from '@/components/site/brand';
import { BurgerIcon, CloseIcon, FacebookIcon, LinkedInIcon, MailIcon, PhoneIcon, PinIcon } from '@/components/site/icons';
import WhatsappButton from '@/components/site/whatsapp-button';
import { useReveal } from '@/hooks/use-reveal';
import type { PageMeta, SharedSiteProps } from '@/types/site';
import { Head, Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren, useEffect, useState } from 'react';
import '../../css/site.css';

const NAV = [
    { label: 'Начало', href: '/' },
    { label: 'За нас', href: '/about' },
    { label: 'Услуги', href: '/services' },
    { label: 'Блог', href: '/blog' },
    { label: 'Поискай оферта', href: '/contact' },
];

interface Props {
    meta?: PageMeta;
}

export default function SiteLayout({ meta, children }: PropsWithChildren<Props>) {
    const page = usePage<SharedSiteProps>();
    const { settings } = page.props;
    const [menuOpen, setMenuOpen] = useState(false);

    const current = page.url.split('?')[0];
    const isActive = (href: string) => (href === '/' ? current === '/' : current.startsWith(href));

    // Reveals the scroll-in animations; without it every .reveal stays at
    // opacity:0 and most of the page looks empty.
    useReveal(page.url);

    // Close the drawer on navigation and lock the body while it is open.
    useEffect(() => setMenuOpen(false), [page.url]);
    useEffect(() => {
        document.body.style.overflow = menuOpen ? 'hidden' : '';
        return () => {
            document.body.style.overflow = '';
        };
    }, [menuOpen]);

    const phone = settings.phone ?? '';
    const phoneHref = `tel:${settings.phone_raw ?? phone.replace(/\s/g, '')}`;

    const title = meta?.title ?? settings.seo_title ?? 'Глобал Карго Спед';
    // Inertia's ziggy payload carries the request URL, which is the only origin
    // available during SSR — window does not exist there.
    const origin = (page.props.ziggy?.url ?? '').replace(/\/$/, '');
    const absoluteUrl = `${origin}${current}`;
    const ogImage = `${origin}/assets/img/hero.webp`;

    return (
        <div className="gcs">
            <Head title={title}>
                {meta?.description && <meta name="description" content={meta.description} head-key="description" />}
                <meta property="og:title" content={title} head-key="ogtitle" />
                {meta?.description && <meta property="og:description" content={meta.description} head-key="ogdesc" />}
                <meta property="og:type" content="website" head-key="ogtype" />
                <meta property="og:site_name" content={settings.site_name ?? ''} head-key="ogsite" />
                <meta property="og:url" content={absoluteUrl} head-key="ogurl" />
                <meta property="og:image" content={ogImage} head-key="ogimage" />
                <meta name="twitter:card" content="summary_large_image" head-key="twcard" />
            </Head>

            <div className="bg-mesh" aria-hidden="true" />
            <div className="bg-orb o1" aria-hidden="true" />
            <div className="bg-orb o2" aria-hidden="true" />
            <div className="bg-grain" aria-hidden="true" />

            <header>
                <div className="wrap">
                    <div className="navbar">
                        <Brand siteName={settings.site_name} />

                        <nav className="navlinks" aria-label="Основна навигация">
                            {NAV.map((item) => (
                                <Link key={item.href} href={item.href} className={isActive(item.href) ? 'active' : undefined}>
                                    {item.label}
                                </Link>
                            ))}
                        </nav>

                        <div className="nav-cta">
                            <a className="callbtn" href={phoneHref}>
                                <span className="ic">
                                    <PhoneIcon />
                                </span>
                                <span>
                                    <small>Обади се</small>
                                    <span>{phone}</span>
                                </span>
                            </a>
                            <Link className="btn btn-accent" href="/contact">
                                Поискай оферта
                            </Link>
                            <button className="burger" aria-label="Меню" aria-expanded={menuOpen} onClick={() => setMenuOpen(true)}>
                                <BurgerIcon />
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            {/* Whole class strings rather than a conditional suffix: the
                tailwind prettier plugin normalises whitespace inside className,
                and silently ate the leading space in `${open ? ' show' : ''}`. */}
            <div className={menuOpen ? 'scrim show' : 'scrim'} onClick={() => setMenuOpen(false)} aria-hidden="true" />

            <aside className={menuOpen ? 'mobile-menu open' : 'mobile-menu'}>
                <div className="mhead">
                    <button className="mclose" aria-label="Затвори" onClick={() => setMenuOpen(false)}>
                        <CloseIcon />
                    </button>
                </div>
                {NAV.map((item) => (
                    <Link key={item.href} href={item.href} className={isActive(item.href) ? 'active' : undefined}>
                        {item.label}
                    </Link>
                ))}
                <a className="btn btn-accent" href={phoneHref}>
                    Обади се сега
                </a>
            </aside>

            <main>{children}</main>

            {/* Renders nothing until a number is set in the admin, so the
                section can be filled in after this ships. */}
            <WhatsappButton
                number={settings.whatsapp_number}
                greeting={settings.whatsapp_greeting}
                teaser={settings.whatsapp_teaser}
                topics={settings.whatsapp_topics}
                page={current}
            />

            <footer>
                <div className="wrap">
                    <div className="glass foot-inner">
                        <div className="foot-grid">
                            <div className="foot-brand">
                                <Brand siteName={settings.site_name} />
                                <p>Вашия товар е наша грижа. Транспорт, складиране и логистика в България, Европа и Турция.</p>
                            </div>

                            <div className="foot">
                                <h5>Навигация</h5>
                                <ul>
                                    {NAV.map((item) => (
                                        <li key={item.href}>
                                            <Link href={item.href}>{item.label}</Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div className="foot">
                                <h5>Услуги</h5>
                                <ul>
                                    {page.props.nav.mainServices.map((service) => (
                                        <li key={service.url}>
                                            <Link href={service.url}>{service.title}</Link>
                                        </li>
                                    ))}
                                    <li>
                                        <Link href="/services">Всички услуги</Link>
                                    </li>
                                </ul>
                            </div>

                            <div className="foot">
                                <h5>Контакти</h5>
                                <ul>
                                    <li className="ct">
                                        <PhoneIcon />
                                        <a href={phoneHref}>{phone}</a>
                                    </li>
                                    <li className="ct">
                                        <MailIcon />
                                        <a href={`mailto:${settings.email ?? ''}`}>{settings.email}</a>
                                    </li>
                                    <li className="ct">
                                        <PinIcon />
                                        <span>{settings.address}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div className="foot-bottom">
                            <span>© {new Date().getFullYear()} Глобал Карго Спед ЕООД. Всички права запазени.</span>
                            <div className="socials">
                                {settings.facebook_url && (
                                    <a href={settings.facebook_url} aria-label="Facebook" rel="noopener noreferrer" target="_blank">
                                        <FacebookIcon />
                                    </a>
                                )}
                                {settings.linkedin_url && (
                                    <a href={settings.linkedin_url} aria-label="LinkedIn" rel="noopener noreferrer" target="_blank">
                                        <LinkedInIcon />
                                    </a>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
