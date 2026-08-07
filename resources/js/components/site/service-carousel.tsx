import { PhotoCard } from './service-grid';
import type { ServiceCard } from '@/types/site';

/**
 * The home page shows services as a marquee rather than a grid. The scrolling
 * is pure CSS (`.services-track` runs the svcMarq animation), and the list is
 * rendered twice so the loop has no visible seam — the same trick the original
 * used. The duplicate is hidden from assistive tech.
 *
 * The cards must not carry `reveal`: they start off-screen inside the track,
 * so the IntersectionObserver would never fire and they would stay invisible.
 */
export default function ServiceCarousel({ services }: { services: ServiceCard[] }) {
    return (
        <div className="services-fullbleed" style={{ marginTop: 8 }}>
            <div className="services-carousel">
                <div className="services-track">
                    {services.map((service) => (
                        <PhotoCard key={service.slug} service={service} reveal={false} />
                    ))}
                    <div aria-hidden="true" style={{ display: 'contents' }}>
                        {services.map((service) => (
                            <PhotoCard key={`dup-${service.slug}`} service={service} reveal={false} />
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
