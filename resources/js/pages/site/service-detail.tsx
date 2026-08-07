import { Link, usePage } from '@inertiajs/react';
import { PhoneIcon } from '@/components/site/icons';
import InquiryForm from '@/components/site/inquiry-form';
import { PhotoCard } from '@/components/site/service-grid';
import SiteLayout from '@/layouts/site-layout';
import type { PageMeta, ServiceCard, ServiceDetail, SharedSiteProps } from '@/types/site';

interface Props {
    service: ServiceDetail;
    related: { data: ServiceCard[] } | ServiceCard[];
    meta: PageMeta;
}

const unwrap = <T,>(value: { data: T[] } | T[]): T[] => (Array.isArray(value) ? value : value.data);

export default function ServiceDetailPage({ service, related, meta }: Props) {
    const { settings } = usePage<SharedSiteProps>().props;
    const others = unwrap(related);
    const phoneHref = `tel:${settings.phone_raw ?? ''}`;

    return (
        <SiteLayout meta={meta}>
            <section className="service-hero">
                {/*
                    Only the image goes in here. The dark wash comes from
                    `.service-hero-media::after`, which is deliberately light.
                    The home page's `.hero-scrim` must NOT be used — it is a
                    near-opaque pale gradient that hides the service photo.
                */}
                <div className="service-hero-media" aria-hidden="true">
                    {service.image && <img src={service.image} alt="" fetchPriority="high" decoding="async" />}
                </div>

                <div className="service-hero-inner">
                    <div className="wrap service-hero-grid">
                        <div className="service-hero-copy">
                            <Link className="sv-back" href="/services">
                                ← Всички услуги
                            </Link>
                            <span className="eyebrow">Услуга</span>
                            <h1>{service.title}</h1>
                            {service.subtitle && <p className="lead">{service.subtitle}</p>}

                            <div className="hero-cta">
                                <a className="btn btn-accent btn-lg" href={phoneHref}>
                                    <PhoneIcon /> {settings.phone}
                                </a>
                                <Link className="btn btn-glass btn-lg" href="/contact">
                                    Поискай оферта
                                </Link>
                            </div>
                        </div>

                        {/* `.service-hero-form` is itself the glass card — no
                            second card nested inside it. */}
                        <div className="glass service-hero-form">
                            <InquiryForm
                                source="service"
                                variant="lead"
                                title="Изпрати запитване"
                                subtitle="Оставете данни за тази услуга — ще ви потърсим до 1 работен ден."
                            />
                        </div>
                    </div>
                </div>
            </section>

            <div className="service-extra">
                <div className="wrap service-body">
                    <div className="service-rich">
                        {service.body
                            ?.split(/\n{2,}/)
                            .filter(Boolean)
                            .map((paragraph, index) => <p key={index}>{paragraph.trim()}</p>)}
                    </div>

                    {others.length > 0 && (
                        <div style={{ marginTop: 'clamp(40px,5vw,60px)' }}>
                            <div className="section-head" style={{ marginBottom: 22, maxWidth: 'none' }}>
                                <span className="eyebrow">Още услуги</span>
                                <h2 style={{ fontSize: 'var(--fs-h3)', marginTop: '.3rem' }}>Другите ни услуги</h2>
                            </div>
                            <div className="subphoto-grid">
                                {others.map((other) => (
                                    <PhotoCard key={other.slug} service={other} />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </SiteLayout>
    );
}
