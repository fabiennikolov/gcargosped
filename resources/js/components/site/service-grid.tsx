import { Link } from '@inertiajs/react';
import type { ServiceCard } from '@/types/site';

interface Props {
    services: ServiceCard[];
    eyebrow?: string;
    heading?: string;
    intro?: string;
}

/**
 * The photo card the original `photoCard()` helper produced: a full-bleed
 * image with the title absolutely positioned over it and a round arrow in the
 * corner. `.subphoto h3` and `.subphoto .go` are both positioned by the
 * stylesheet, so the nesting has to stay exactly this shape.
 */
export function PhotoCard({ service, reveal = true }: { service: ServiceCard; reveal?: boolean }) {
    return (
        <Link className={reveal ? 'subphoto reveal' : 'subphoto'} href={service.url}>
            {service.image && <img src={service.image} alt={service.title} loading="lazy" decoding="async" />}
            <h3>{service.title}</h3>
            <span className="go">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.4} strokeLinecap="round" strokeLinejoin="round">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </span>
        </Link>
    );
}

export default function ServiceGrid({ services, eyebrow, heading, intro }: Props) {
    return (
        <section>
            <div className="wrap">
                {heading && (
                    <div className="section-head reveal">
                        {eyebrow && <span className="eyebrow">{eyebrow}</span>}
                        <h2>{heading}</h2>
                        {intro && <p>{intro}</p>}
                    </div>
                )}

                <div className="subphoto-grid">
                    {services.map((service) => (
                        <PhotoCard key={service.slug} service={service} />
                    ))}
                </div>
            </div>
        </section>
    );
}
