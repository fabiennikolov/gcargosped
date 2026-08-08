import SiteLayout from '@/layouts/site-layout';
import type { PageMeta, SharedSiteProps } from '@/types/site';
import { Link, usePage } from '@inertiajs/react';

interface Props {
    meta: PageMeta;
}

const TILES = [
    { n: '15+', label: 'Години опит' },
    { n: '200+', label: 'Доволни клиенти' },
    { n: '50+', label: 'Собствени камиони' },
    { n: '100+', label: 'Нает транспорт' },
];

/** The six value icons, lifted from the original markup. */
const VALUES = [
    {
        title: 'Отговорност',
        text: 'Поемаме ангажимент към всеки товар и го изпълняваме докрай.',
        path: <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />,
    },
    {
        title: 'Коректност',
        text: 'Честни условия и коректно отношение към всеки клиент.',
        path: (
            <>
                <circle cx="12" cy="12" r="10" />
                <path d="M8 12l3 3 5-6" />
            </>
        ),
    },
    {
        title: 'Честност',
        text: 'Прозрачност във всяка стъпка от процеса.',
        path: (
            <>
                <path d="M12 3v18M5 8l7-3 7 3" />
                <path d="M5 8l-2 6a4 4 0 0 0 8 0zM19 8l-2 6a4 4 0 0 0 8 0z" />
            </>
        ),
    },
    {
        title: 'Добронамереност',
        text: 'Отношение и грижа, както към собствена стока.',
        path: <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21l8.84-8.61a5.5 5.5 0 0 0 0-7.78z" />,
    },
    {
        title: 'Откритост',
        text: 'Ясна комуникация и достъпност по всяко време.',
        path: (
            <>
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                <circle cx="12" cy="12" r="3" />
            </>
        ),
    },
    {
        title: 'Професионализъм',
        text: 'Екип от квалифицирани специалисти с дългогодишен опит.',
        path: (
            <>
                <circle cx="12" cy="8" r="6" />
                <path d="M8.5 13.5L7 22l5-3 5 3-1.5-8.5" />
            </>
        ),
    },
];

export default function About({ meta }: Props) {
    const { settings } = usePage<SharedSiteProps>().props;

    return (
        <SiteLayout meta={meta}>
            <section>
                <div className="wrap">
                    {/* .about-hero is a two-column grid — copy beside the stat
                        tiles — not a page header. */}
                    <div className="about-hero">
                        <div className="about-copy">
                            <span className="eyebrow">За нас</span>
                            <h1 style={{ fontSize: 'var(--fs-h2)', margin: '.6rem 0 1.1rem' }}>„Вашият товар е наша грижа.“</h1>
                            <p className="muted" style={{ fontSize: '1.08rem' }}>
                                Това не е просто нашето мото. Това е обещание към всеки клиент, който ни се доверява.
                            </p>
                            <p className="muted">
                                В GLOBAL CARGO SPED вярваме, че зад всяка пратка стоят нечий бизнес, време, усилия и отговорност. Затова подхождаме
                                към всеки превоз с професионализъм, прецизност и лично отношение, независимо от неговия размер или дестинация.
                            </p>
                            <p className="muted">
                                Ние предлагаме надеждни решения за международен автомобилен транспорт и логистика в цяла Европа. Благодарение на
                                дългогодишния ни опит, широка мрежа от доказани партньори и индивидуален подход към всеки клиент, гарантираме
                                сигурност, точност и ефективност на всяка доставка.
                            </p>
                            <p className="muted">
                                Нашата мисия е да осигурим спокойствие на клиентите си, като поемем цялостната организация на транспорта – от първата
                                заявка до успешното разтоварване.
                            </p>
                            <p className="muted">
                                За нас коректността не е предимство – тя е стандарт. Доверието не се обещава – то се изгражда с всяка успешно
                                изпълнена доставка.
                            </p>
                            <p style={{ fontWeight: 700, color: 'var(--ink)', fontSize: '1.08rem' }}>
                                GLOBAL CARGO SPED – Вашият надежден партньор в международния транспорт и логистиката.
                                <br />
                                Вашият товар е наша грижа.
                            </p>
                        </div>

                        <div className="stat-tiles">
                            {TILES.map((tile) => (
                                <div className="glass tile reveal" key={tile.label}>
                                    <b className="tnum">{tile.n}</b>
                                    <span>{tile.label}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="wrap stack-y-sm">
                    <div className="glass photo-band reveal">
                        <img
                            className="photo"
                            src="/assets/img/tovari-evropa.webp"
                            alt="Превоз на товари в цяла Европа"
                            decoding="async"
                            loading="lazy"
                        />
                        <span className="photo-cap">Превоз на товари в цяла Европа</span>
                    </div>
                </div>

                <div className="wrap stack-y">
                    <div className="section-head center reveal">
                        <span className="eyebrow">Нашите ценности</span>
                        <h2 style={{ fontSize: 'var(--fs-h2)' }}>Ценностите, които ни дават сили</h2>
                    </div>

                    <div className="values">
                        {VALUES.map((value) => (
                            <div className="glass value reveal" key={value.title}>
                                <div className="vi">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth={2}
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        {value.path}
                                    </svg>
                                </div>
                                <h4>{value.title}</h4>
                                <p>{value.text}</p>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="wrap stack-y">
                    <div className="cta-banner reveal">
                        <div className="in">
                            <h2>Нека поемем вашата логистика</h2>
                            <p>Свържете се с нас за индивидуално решение, съобразено с нуждите на вашия бизнес.</p>
                            <div className="btns">
                                <Link className="btn btn-accent btn-lg" href="/contact">
                                    {settings.hero_cta ?? 'Поискай оферта'}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </SiteLayout>
    );
}
