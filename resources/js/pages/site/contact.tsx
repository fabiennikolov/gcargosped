import { usePage } from '@inertiajs/react';
import InquiryForm from '@/components/site/inquiry-form';
import { ClockIcon, MailIcon, PhoneIcon, PinIcon } from '@/components/site/icons';
import SiteLayout from '@/layouts/site-layout';
import type { PageMeta, SharedSiteProps } from '@/types/site';

interface Props {
    meta: PageMeta;
}

export default function Contact({ meta }: Props) {
    const { settings } = usePage<SharedSiteProps>().props;
    const phoneHref = `tel:${settings.phone_raw ?? (settings.phone ?? '').replace(/\s/g, '')}`;

    /**
     * `.contact-line .ci` is the 44px rounded green tile the icon sits in.
     * Without it the raw SVG stretches to the width of the card.
     */
    const line = (icon: React.ReactNode, label: string, value: React.ReactNode) => (
        <div className="contact-line">
            <span className="ci">{icon}</span>
            <div>
                <small>{label}</small>
                <b>{value}</b>
            </div>
        </div>
    );

    return (
        <SiteLayout meta={meta}>
            <section>
                <div className="wrap offer-grid">
                    {/* Narrow column first — .offer-grid is .82fr / 1.18fr. */}
                    <div className="glass offer-aside">
                        <span className="chip">Безплатна оферта</span>
                        <h2>Поискай оферта</h2>
                        <p className="lead">
                            Изпратете запитване или се свържете с нас за оферта. Попълнете детайлите за товара и
                            маршрута — ще ви отговорим възможно най-бързо.
                        </p>

                        {line(<PhoneIcon />, 'Телефон', <a href={phoneHref}>{settings.phone}</a>)}
                        {line(<MailIcon />, 'Имейл', <a href={`mailto:${settings.email ?? ''}`}>{settings.email}</a>)}
                        {line(<PinIcon />, 'Адрес', settings.address)}
                        {settings.working_hours && line(<ClockIcon />, 'Работно време', settings.working_hours)}
                    </div>

                    <div className="glass offer-form">
                        <InquiryForm source="contact" variant="offer" />
                    </div>
                </div>
            </section>
        </SiteLayout>
    );
}
