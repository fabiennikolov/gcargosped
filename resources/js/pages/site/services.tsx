import { Link } from '@inertiajs/react';
import { PhotoCard } from '@/components/site/service-grid';
import SiteLayout from '@/layouts/site-layout';
import type { PageMeta, ServiceCard } from '@/types/site';

interface Props {
    services: { data: ServiceCard[] } | ServiceCard[];
    meta: PageMeta;
}

const unwrap = <T,>(value: { data: T[] } | T[]): T[] => (Array.isArray(value) ? value : value.data);

export default function Services({ services, meta }: Props) {
    return (
        <SiteLayout meta={meta}>
            <section>
                <div className="wrap">
                    <div className="section-head reveal">
                        <span className="eyebrow">Услуги</span>
                        <h1 style={{ fontSize: 'var(--fs-h2)', margin: '.6rem 0 .7rem' }}>Пълен набор от услуги</h1>
                        <p>
                            Превозваме почти всякакъв товар — из България, Европа и Турция, с подходящото за целта
                            превозно средство.
                        </p>
                    </div>

                    <div className="subphoto-grid">
                        {unwrap(services).map((service) => (
                            <PhotoCard key={service.slug} service={service} />
                        ))}
                    </div>

                    <div
                        className="glass reveal"
                        style={{
                            marginTop: 40,
                            padding: 28,
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            gap: 22,
                            flexWrap: 'wrap',
                        }}
                    >
                        <div>
                            <h3 style={{ fontSize: 'var(--fs-h3)', marginBottom: '.3rem' }}>
                                Не сте сигурни коя услуга ви трябва?
                            </h3>
                            <p className="muted">
                                Нашите служители са винаги на разположение да ви обърнат внимание — безплатна
                                консултация.
                            </p>
                        </div>
                        <Link className="btn btn-dark btn-lg" href="/contact">
                            Поискай безплатна консултация
                        </Link>
                    </div>
                </div>
            </section>
        </SiteLayout>
    );
}
