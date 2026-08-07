import { useForm, usePage } from '@inertiajs/react';
import { type FormEvent, type ReactNode } from 'react';
import { CheckIcon } from './icons';
import type { SharedSiteProps } from '@/types/site';

interface Props {
    /** Which form this is, so the admin can tell offers from contact messages. */
    source: 'offer' | 'contact' | 'service';

    /**
     * `lead` is the compact card in the hero — name, phone, e-mail only.
     * `offer` is the full quote form with route and cargo type.
     */
    variant: 'lead' | 'offer';

    serviceId?: number;
    title?: string;
    subtitle?: string;
}

/** The cargo types the original select offered, in the same order. */
const CARGO_TYPES = [
    'Кола / Автомобил',
    'Палет',
    'Ремарке',
    'Кашон / Каса',
    'Машина / Оборудване',
    'Групажен товар',
    'Метали (гондола)',
];

const CITIES = [
    'София',
    'Пловдив',
    'Варна',
    'Бургас',
    'Русе',
    'Стара Загора',
    'Истанбул, Турция',
    'Мюнхен, Германия',
    'Виена, Австрия',
    'Милано, Италия',
    'Букурещ, Румъния',
    'Солун, Гърция',
];

const OTHER = 'other';

export default function InquiryForm({ source, variant, serviceId, title, subtitle }: Props) {
    const page = usePage<SharedSiteProps>();
    const flash = page.props.flash?.inquiry;

    const form = useForm({
        name: '',
        phone: '',
        email: '',
        cargo_type: '',
        cargo_other: '',
        origin: '',
        destination: '',
        message: '',
        source,
        service_id: serviceId ?? null,
        website: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();

        // transform() returns void in this version, so it is applied first and
        // post() called separately.
        form.transform((data) => ({
            ...data,
            // "Друго…" sends the typed description rather than the sentinel.
            cargo_type: data.cargo_type === OTHER ? data.cargo_other : data.cargo_type,
        }));

        form.post('/inquiries', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    if (flash?.ok) {
        return (
            <div className="form-ok show" role="status">
                <div className="ck">
                    <CheckIcon />
                </div>
                <h3>Заявката е изпратена</h3>
                <p>{flash.message}</p>
            </div>
        );
    }

    // The stylesheet reveals .errmsg through `.input.err ~ .errmsg`, so the
    // error state has to sit on the input itself.
    const cls = (field: string) => (form.errors[field as keyof typeof form.errors] ? 'input err' : 'input');

    const field = (
        name: 'name' | 'phone' | 'email' | 'origin' | 'destination',
        label: string,
        required: boolean,
        input: ReactNode,
        fallbackError: string,
    ) => (
        <div className="field">
            <label htmlFor={`${name}-${source}`}>
                {label} {required && <span className="req">*</span>}
            </label>
            {input}
            <div className="errmsg">{form.errors[name] ?? fallbackError}</div>
        </div>
    );

    const nameField = field(
        'name',
        'Име',
        true,
        <input
            id={`name-${source}`}
            className={cls('name')}
            name="name"
            type="text"
            placeholder={variant === 'lead' ? 'Вашето име' : 'Име и фамилия'}
            autoComplete="name"
            value={form.data.name}
            onChange={(e) => form.setData('name', e.target.value)}
        />,
        'Моля, въведете име.',
    );

    const phoneField = field(
        'phone',
        'Телефон',
        true,
        <input
            id={`phone-${source}`}
            className={cls('phone')}
            name="phone"
            type="tel"
            placeholder="+359 ..."
            autoComplete="tel"
            value={form.data.phone}
            onChange={(e) => form.setData('phone', e.target.value)}
        />,
        'Моля, въведете валиден телефон.',
    );

    const emailField = field(
        'email',
        'Имейл',
        variant === 'offer',
        <input
            id={`email-${source}`}
            className={cls('email')}
            name="email"
            type="email"
            placeholder="you@company.com"
            autoComplete="email"
            value={form.data.email}
            onChange={(e) => form.setData('email', e.target.value)}
        />,
        variant === 'offer' ? 'Моля, въведете валиден имейл.' : 'Моля, проверете имейла.',
    );

    const honeypot = (
        <div aria-hidden="true" style={{ position: 'absolute', left: '-9999px', opacity: 0 }}>
            <label htmlFor={`website-${source}`}>Website</label>
            <input
                id={`website-${source}`}
                type="text"
                tabIndex={-1}
                autoComplete="off"
                value={form.data.website}
                onChange={(e) => form.setData('website', e.target.value)}
            />
        </div>
    );

    if (variant === 'lead') {
        return (
            <div className="leadform">
                <h3>{title ?? 'Изпрати запитване'}</h3>
                <p className="sub">{subtitle ?? 'Оставете данни — ще ви потърсим до 1 работен ден.'}</p>

                <form onSubmit={submit} noValidate>
                    {nameField}
                    {phoneField}
                    {emailField}
                    {honeypot}
                    <button
                        className="btn btn-accent"
                        style={{ width: '100%', marginTop: 4 }}
                        type="submit"
                        disabled={form.processing}
                    >
                        {form.processing ? 'Изпраща се…' : 'Свържете се с мен'}
                    </button>
                </form>
            </div>
        );
    }

    return (
        <form onSubmit={submit} noValidate>
            <div className="form-row">
                {nameField}
                {phoneField}
            </div>

            {emailField}

            <div className="route-fields">
                {field(
                    'origin',
                    'Начална дестинация',
                    false,
                    <input
                        id={`origin-${source}`}
                        className={cls('origin')}
                        name="origin"
                        type="text"
                        placeholder="Град / адрес на товарене"
                        autoComplete="off"
                        list="cities"
                        value={form.data.origin}
                        onChange={(e) => form.setData('origin', e.target.value)}
                    />,
                    '',
                )}

                {field(
                    'destination',
                    'Крайна дестинация',
                    false,
                    <input
                        id={`destination-${source}`}
                        className={cls('destination')}
                        name="destination"
                        type="text"
                        placeholder="Град / адрес на доставка"
                        autoComplete="off"
                        list="cities"
                        value={form.data.destination}
                        onChange={(e) => form.setData('destination', e.target.value)}
                    />,
                    '',
                )}
            </div>

            <datalist id="cities">
                {CITIES.map((city) => (
                    <option key={city} value={city} />
                ))}
            </datalist>

            <div className="field">
                <label htmlFor={`cargo-${source}`}>Тип товар</label>
                <select
                    id={`cargo-${source}`}
                    className="input"
                    name="cargo_type"
                    value={form.data.cargo_type}
                    onChange={(e) => form.setData('cargo_type', e.target.value)}
                >
                    <option value="">Изберете тип товар…</option>
                    {CARGO_TYPES.map((type) => (
                        <option key={type} value={type}>
                            {type}
                        </option>
                    ))}
                    <option value={OTHER}>Друго…</option>
                </select>
            </div>

            {form.data.cargo_type === OTHER && (
                <div className="field">
                    <label htmlFor={`cargo-other-${source}`}>Опишете товара</label>
                    <input
                        id={`cargo-other-${source}`}
                        className="input"
                        name="cargo_other"
                        type="text"
                        placeholder="Какъв е товарът?"
                        value={form.data.cargo_other}
                        onChange={(e) => form.setData('cargo_other', e.target.value)}
                    />
                </div>
            )}

            <div className="field">
                <label htmlFor={`message-${source}`}>Допълнителна информация</label>
                <textarea
                    id={`message-${source}`}
                    className={cls('message')}
                    name="message"
                    placeholder="Тегло, размери, дата на товарене, специални изисквания…"
                    value={form.data.message}
                    onChange={(e) => form.setData('message', e.target.value)}
                />
                <p className="hint">По желание — колкото повече детайли, толкова по-точна оферта.</p>
            </div>

            {honeypot}

            <button className="btn btn-accent btn-lg" style={{ width: '100%' }} type="submit" disabled={form.processing}>
                {form.processing ? 'Изпраща се…' : 'Изпрати запитване'}
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4z" />
                </svg>
            </button>
        </form>
    );
}
