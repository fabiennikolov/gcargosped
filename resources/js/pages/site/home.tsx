import { ArrowIcon, CheckIcon } from '@/components/site/icons';
import InquiryForm from '@/components/site/inquiry-form';
import PartnerGrid from '@/components/site/partner-grid';
import ServiceCarousel from '@/components/site/service-carousel';
import SiteLayout from '@/layouts/site-layout';
import type { PageMeta, Partner, ServiceCard, SharedSiteProps } from '@/types/site';
import { Link, usePage } from '@inertiajs/react';
import type { CSSProperties } from 'react';

interface Props {
    services: { data: ServiceCard[] } | ServiceCard[];
    partners: Partner[];
    meta: PageMeta;
}

/** Resource collections arrive wrapped in `data`; plain arrays do not. */
const unwrap = <T,>(value: { data: T[] } | T[]): T[] => (Array.isArray(value) ? value : value.data);

const TRUST = ['Европа · Турция · България', 'Товари до 24 тона'];

interface Stat {
    value: string;
    label: string;
}

/** Shown until someone fills the list in under Настройки на сайта. */
const DEFAULT_STATS: Stat[] = [
    { value: '15+', label: 'години опит' },
    { value: '200+', label: 'клиенти' },
    { value: '50+', label: 'собствени камиони' },
    { value: '100+', label: 'нает транспорт' },
];

/**
 * The admin stores the band as a JSON array of rows. Anything else in there —
 * an empty setting, a hand-edited value — falls back to the figures above
 * rather than leaving the band blank.
 */
function parseStats(raw?: string): Stat[] {
    const value = (raw ?? '').trim();
    if (!value) return DEFAULT_STATS;

    try {
        const parsed: unknown = JSON.parse(value);
        if (!Array.isArray(parsed)) return DEFAULT_STATS;

        const rows = parsed
            .map((row) => ({
                value: String((row as Stat)?.value ?? '').trim(),
                label: String((row as Stat)?.label ?? '').trim(),
            }))
            .filter((row) => row.value !== '' && row.label !== '');

        return rows.length > 0 ? rows : DEFAULT_STATS;
    } catch {
        return DEFAULT_STATS;
    }
}

/**
 * "15+" is drawn as a big 15 with the + in green beside it, so the number and
 * whatever trails it are split apart. A value that does not start with digits
 * ("над 15") is left whole rather than mangled.
 */
function splitStat(value: string): [string, string] {
    const match = /^([\d\s.,]+)(.*)$/.exec(value.trim());

    return match ? [match[1].trim(), match[2].trim()] : [value, ''];
}

const REGIONS = [
    { fx: 'BG', title: 'България', text: 'Вътрешен транспорт и дистрибуция до всяка точка в страната.' },
    { fx: 'EU', title: 'Европа', text: 'Редовни курсове до и от държавите в Европейския съюз — цели камиони и групаж.' },
    { fx: 'TR', title: 'Турция', text: 'Специализирани маршрути за внос и износ до и от Турция.' },
];

const STEPS = [
    { n: '01', t: 'Запитване', d: 'Изпращате детайли за товара и маршрута през формата или по телефон.' },
    { n: '02', t: 'Оферта', d: 'Получавате прозрачна цена и срок — без скрити условия.' },
    { n: '03', t: 'Транспорт', d: 'Товарим и превозваме с подходящото за товара превозно средство.' },
    { n: '04', t: 'Доставка', d: 'Доставяме навреме и оставаме на разположение за обратна връзка.' },
];

export default function Home({ services, partners, meta }: Props) {
    const { settings } = usePage<SharedSiteProps>().props;
    const all = unwrap(services);
    const stats = parseStats(settings.stats);

    // The first chip repeats the first figure, so the two cannot disagree once
    // the numbers are edited in the admin.
    const trust = [`${stats[0].value} ${stats[0].label}`, ...TRUST];

    return (
        <SiteLayout meta={meta}>
            <section className="hero">
                <div className="hero-media" aria-hidden="true">
                    <img
                        className="hero-bgimg"
                        src="/assets/img/hero.webp"
                        alt="Камион на Глобал Карго Спед на магистрала — транспорт в Европа"
                        fetchPriority="high"
                        decoding="async"
                    />
                    <video className="hero-video" autoPlay muted loop playsInline preload="metadata">
                        <source src="/assets/bridge-truck.mp4" type="video/mp4" />
                    </video>
                    <div className="hero-scrim" />
                </div>

                <div className="hero-inner">
                    <div className="wrap hero-grid">
                        <div className="hero-copy">
                            <span className="hero-badge">
                                <span className="dot" /> На линия за нови запитвания
                            </span>

                            <h1 dangerouslySetInnerHTML={{ __html: settings.hero_title ?? '' }} />
                            <p className="lead">{settings.hero_subtitle}</p>

                            <div className="hero-cta">
                                <Link className="btn btn-accent btn-lg" href="/contact">
                                    {settings.hero_cta ?? 'Поискай оферта'}
                                    <ArrowIcon />
                                </Link>
                                <Link className="btn btn-glass btn-lg" href="/services">
                                    Нашите услуги
                                </Link>
                            </div>
                        </div>

                        <div className="hero-visual">
                            <div className="glass hero-card">
                                <InquiryForm source="offer" variant="lead" />
                            </div>
                        </div>

                        {/* A sibling of the form rather than a child of the copy so that the
                            single-column mobile layout can place it after the form. Desktop
                            grid placement puts it back under the copy. */}
                        <div className="hero-trust">
                            {trust.map((item) => (
                                <span className="chip" key={item}>
                                    <CheckIcon /> {item}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <div className="wrap">
                    <div className="section-head reveal">
                        <span className="eyebrow">Нашите услуги</span>
                        <h2>Пълен набор от услуги</h2>
                        <p>От малки колети до големи товарни пратки — разполагаме с експертизата и ресурсите за всяко ваше транспортно изискване.</p>
                    </div>
                </div>

                <ServiceCarousel services={all} />

                <div className="wrap">
                    <div className="section-cta reveal">
                        <Link className="btn btn-glass btn-lg" href="/services">
                            Виж всички услуги <ArrowIcon />
                        </Link>
                    </div>
                </div>
            </section>

            <section className="section-tight">
                <div className="wrap">
                    <div className="band reveal">
                        {/* The column count follows the list rather than being
                            fixed at four, so removing a figure in the admin
                            leaves three even columns instead of a gap. */}
                        <div className="inner" style={{ '--st-count': stats.length } as CSSProperties}>
                            {stats.map((stat, index) => {
                                const [number, unit] = splitStat(stat.value);

                                return (
                                    <div className="st" key={`${stat.label}-${index}`}>
                                        <b className="tnum">
                                            {number}
                                            {unit && <span className="u">{unit}</span>}
                                        </b>
                                        <span>{stat.label}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </section>

            <section className="section-tight">
                <div className="wrap coverage">
                    <div className="glass map-lg reveal">
                        <div className="map-frame">
                            <svg viewBox="0 0 420 320" role="img" aria-label="Маршрут България — Европа — Турция">
                                <rect width="420" height="320" rx="18" fill="#E6F8EF" />
                                <path
                                    d="M60 250 C 130 240, 150 150, 210 150 C 270 150, 280 90, 350 70"
                                    fill="none"
                                    stroke="#0B8358"
                                    strokeWidth="3"
                                    strokeDasharray="9 9"
                                />
                                <circle cx="60" cy="250" r="9" fill="#0E9F6E" />
                                <circle cx="210" cy="150" r="9" fill="#0B8358" />
                                <circle cx="350" cy="70" r="9" fill="#171B23" />
                                <text x="60" y="278" textAnchor="middle" fontSize="15" fontWeight="700" fill="#14181F">
                                    България
                                </text>
                                <text x="210" y="180" textAnchor="middle" fontSize="15" fontWeight="700" fill="#14181F">
                                    Европа
                                </text>
                                <text x="350" y="100" textAnchor="middle" fontSize="15" fontWeight="700" fill="#14181F">
                                    Турция
                                </text>
                            </svg>
                        </div>
                    </div>

                    <div>
                        <span className="eyebrow reveal">За нас</span>
                        <h2 className="reveal" style={{ fontSize: 'var(--fs-h2)', margin: '.5rem 0 1rem' }}>
                            Превоз на товари от и за Европа
                        </h2>
                        <p className="muted reveal" style={{ marginBottom: '1.4rem' }}>
                            От малки колети до големи товарни пратки, ние разполагаме с експертизата и ресурсите, за да се справим с всички ваши
                            транспортни изисквания с прецизност и грижа.
                        </p>

                        <div className="region-list">
                            {REGIONS.map((region) => (
                                <div className="region reveal" key={region.fx}>
                                    <span className="fx">{region.fx}</span>
                                    <div>
                                        <h4>{region.title}</h4>
                                        <p>{region.text}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <section className="section-tight">
                <div className="wrap">
                    <div className="section-head center reveal">
                        <span className="eyebrow">Как работим</span>
                        <h2>Четири стъпки до доставката</h2>
                    </div>
                    <div className="steps">
                        {STEPS.map((step) => (
                            <div className="glass step reveal" key={step.n}>
                                <div className="num">{step.n}</div>
                                <h3>{step.t}</h3>
                                <p>{step.d}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="section-tight">
                <div className="wrap">
                    <div className="section-head center reveal">
                        <span className="eyebrow">Нашите партньори</span>
                        <h2>Компании, които ни се довериха</h2>
                    </div>
                    <PartnerGrid partners={partners} />
                </div>
            </section>

            <section className="section-tight">
                <div className="wrap">
                    <div className="cta-banner reveal">
                        <div className="in">
                            <h2>Имате товар за превоз?</h2>
                            <p>Изпратете запитване или се свържете с нас за оферта, съобразена с вашия маршрут и вид товар.</p>
                            <div className="btns">
                                <Link className="btn btn-accent btn-lg" href="/contact">
                                    Поискай оферта
                                </Link>
                                <a
                                    className="btn btn-glass btn-lg"
                                    href={`tel:${settings.phone_raw ?? ''}`}
                                    style={{
                                        color: '#fff',
                                        background: 'rgba(255,255,255,.14)',
                                        borderColor: 'rgba(255,255,255,.3)',
                                    }}
                                >
                                    {settings.phone}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </SiteLayout>
    );
}
