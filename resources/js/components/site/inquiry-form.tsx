import { reportLead } from '@/lib/marketing';
import type { FormField, SharedSiteProps } from '@/types/site';
import { useForm, usePage } from '@inertiajs/react';
import { useEffect, type FormEvent, type ReactNode } from 'react';
import { CheckIcon } from './icons';

interface Props {
    /** Which form this is, so the admin can tell offers from contact messages. */
    source: 'offer' | 'contact' | 'service';

    /**
     * `lead` is the compact card in the hero and on service pages; `offer` is
     * the full quote form on /contact. Which fields each one shows, in which
     * order and with which wording is set in the admin (Форми) and arrives in
     * the shared `forms` prop.
     */
    variant: 'lead' | 'offer';

    serviceId?: number;
}

/**
 * The heading and the line under it on the compact form. Both are editable in
 * the admin (Настройки на сайта → Форма за запитване); these are what a site
 * that has never opened that section shows.
 *
 * Deliberately no callback deadline in the default: a promise baked into the
 * markup is one the office never agreed to and cannot take back.
 */
const DEFAULT_TITLE = 'Изпрати запитване';
const DEFAULT_SUBTITLE = 'Оставете данни и ще ви потърсим с оферта.';

const DEFAULT_BUTTON = { lead: 'Свържете се с мен', offer: 'Изпрати запитване' };

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

const FREQUENCIES = [
    { value: 'one_time', label: 'Еднократен' },
    { value: 'regular', label: 'Редовен' },
];

const OTHER = 'other';

/** Fallback messages for fields whose server error is not specific. */
const FALLBACK_ERRORS: Record<string, string> = {
    name: 'Моля, въведете име.',
    phone: 'Моля, въведете валиден телефон.',
    email: 'Моля, проверете имейла.',
};

const AUTOCOMPLETE: Record<string, string> = { name: 'name', phone: 'tel', email: 'email' };

/** Consecutive half-width fields share a row; everything else gets its own. */
function layout(fields: FormField[]): FormField[][] {
    const rows: FormField[][] = [];

    for (const field of fields) {
        const last = rows[rows.length - 1];

        if (field.half && last && last.length === 1 && last[0].half) {
            last.push(field);
        } else {
            rows.push([field]);
        }
    }

    return rows;
}

type FormData = Record<string, string | number | null | Record<string, string | boolean>>;

export default function InquiryForm({ source, variant, serviceId }: Props) {
    const page = usePage<SharedSiteProps>();
    const { settings } = page.props;
    const config = page.props.forms?.[variant] ?? { fields: [], button: null };
    const flash = page.props.flash?.inquiry;

    const initial: FormData = {
        source,
        service_id: serviceId ?? null,
        website: '',
        cargo_other: '',
        extra: {},
    };
    for (const field of config.fields) {
        if (field.builtin) {
            initial[field.key] = '';
        }
    }

    const form = useForm<FormData>(initial);
    const errors = form.errors as Record<string, string | undefined>;
    const extra = (form.data.extra ?? {}) as Record<string, string | boolean>;

    /*
     * The lead, and the only place one is reported — to every platform
     * configured in the admin (see lib/marketing). It is deliberately tied
     * to the thank-you card rather than to the submit button or to a page
     * load: the button fires on a request that validation can still reject,
     * and a page load is somebody reading, not somebody asking for an offer.
     *
     * An effect never runs during the SSR render, so the server cannot report
     * a lead it is only drawing the HTML for.
     */
    useEffect(() => {
        if (flash?.ok) {
            reportLead(settings, flash.id, source);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash?.ok, flash?.id]);

    function submit(event: FormEvent) {
        event.preventDefault();

        // transform() returns void in this version, so it is applied first and
        // post() called separately.
        form.transform((data) => {
            const { cargo_other, ...rest } = data;

            // "Друго…" sends the typed description rather than the sentinel.
            return rest.cargo_type === OTHER ? { ...rest, cargo_type: cargo_other } : rest;
        });

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

    const errorKey = (field: FormField) => (field.builtin ? field.key : `extra.${field.key}`);

    const value = (field: FormField): string => {
        const raw = field.builtin ? form.data[field.key] : extra[field.key];

        return typeof raw === 'string' || typeof raw === 'number' ? String(raw) : '';
    };

    const set = (field: FormField, next: string | boolean) => {
        if (field.builtin) {
            form.setData(field.key, next as string);
        } else {
            form.setData('extra', { ...extra, [field.key]: next });
        }
    };

    const control = (field: FormField): ReactNode => {
        const id = `${field.key}-${source}`;
        // The stylesheet reveals .errmsg through `.input.err ~ .errmsg`, so
        // the error state has to sit on the input itself.
        const className = errors[errorKey(field)] ? 'input err' : 'input';
        const common = {
            id,
            className,
            name: field.key,
            placeholder: field.placeholder || undefined,
            value: value(field),
        };

        if (field.type === 'textarea') {
            return <textarea {...common} onChange={(e) => set(field, e.target.value)} />;
        }

        if (field.type === 'select') {
            const options = field.key === 'frequency' ? FREQUENCIES : field.options.map((option) => ({ value: option, label: option }));

            return (
                <select {...common} onChange={(e) => set(field, e.target.value)}>
                    <option value="">{field.placeholder || 'Изберете…'}</option>
                    {options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                    {field.key === 'cargo_type' && <option value={OTHER}>Друго…</option>}
                </select>
            );
        }

        const list = field.key === 'origin' || field.key === 'destination' ? 'cities' : field.options.length > 0 ? `options-${id}` : undefined;

        return (
            <>
                <input
                    {...common}
                    type={field.type}
                    inputMode={field.type === 'number' ? 'numeric' : undefined}
                    min={field.type === 'number' && field.builtin ? 1 : undefined}
                    autoComplete={AUTOCOMPLETE[field.key] ?? 'off'}
                    list={list}
                    onChange={(e) => set(field, e.target.value)}
                />
                {list === `options-${id}` && (
                    <datalist id={list}>
                        {field.options.map((option) => (
                            <option key={option} value={option} />
                        ))}
                    </datalist>
                )}
            </>
        );
    };

    const renderField = (field: FormField): ReactNode => {
        const id = `${field.key}-${source}`;
        const error = errors[errorKey(field)];

        if (field.type === 'checkbox') {
            return (
                <div className="field field-check" key={field.key}>
                    <label htmlFor={id}>
                        <input id={id} type="checkbox" checked={extra[field.key] === true} onChange={(e) => set(field, e.target.checked)} />{' '}
                        {field.label} {field.required && <span className="req">*</span>}
                    </label>
                    {field.help && <p className="hint">{field.help}</p>}
                    {error && (
                        <div className="errmsg" style={{ display: 'block' }}>
                            {error}
                        </div>
                    )}
                </div>
            );
        }

        return (
            <div className="field" key={field.key}>
                <label htmlFor={id}>
                    {field.label} {field.required && <span className="req">*</span>}
                </label>
                {control(field)}
                <div className="errmsg">{error ?? FALLBACK_ERRORS[field.key] ?? ''}</div>
                {field.help && <p className="hint">{field.help}</p>}
                {field.key === 'cargo_type' && value(field) === OTHER && (
                    <input
                        id={`cargo-other-${source}`}
                        className="input"
                        style={{ marginTop: 8 }}
                        name="cargo_other"
                        type="text"
                        aria-label="Опишете товара"
                        placeholder="Какъв е товарът?"
                        value={String(form.data.cargo_other ?? '')}
                        onChange={(e) => form.setData('cargo_other', e.target.value)}
                    />
                )}
            </div>
        );
    };

    /*
     * Rows in the admin's order. "From" directly followed by "to" keeps the
     * route line between them (.route-fields) — but only when both are on
     * their own rows, which is how the design draws it.
     */
    const rows = layout(config.fields);
    const body: ReactNode[] = [];

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const next = rows[i + 1];

        if (row.length === 1 && row[0].key === 'origin' && next?.length === 1 && next[0].key === 'destination') {
            body.push(
                <div className="route-fields" key="route">
                    {renderField(row[0])}
                    {renderField(next[0])}
                </div>,
            );
            i++;
            continue;
        }

        body.push(
            row.length === 2 ? (
                <div className="form-row" key={row[0].key}>
                    {row.map(renderField)}
                </div>
            ) : (
                renderField(row[0])
            ),
        );
    }

    const usesCities = config.fields.some((field) => field.key === 'origin' || field.key === 'destination');

    const honeypot = (
        <div aria-hidden="true" style={{ position: 'absolute', left: '-9999px', opacity: 0 }}>
            <label htmlFor={`website-${source}`}>Website</label>
            <input
                id={`website-${source}`}
                type="text"
                tabIndex={-1}
                autoComplete="off"
                value={String(form.data.website ?? '')}
                onChange={(e) => form.setData('website', e.target.value)}
            />
        </div>
    );

    const buttonText = form.processing ? 'Изпраща се…' : config.button || DEFAULT_BUTTON[variant];

    const fields = (
        <>
            {body}
            {usesCities && (
                <datalist id="cities">
                    {CITIES.map((city) => (
                        <option key={city} value={city} />
                    ))}
                </datalist>
            )}
            {honeypot}
        </>
    );

    if (variant === 'lead') {
        return (
            <div className="leadform">
                <h3>{settings.inquiry_title?.trim() || DEFAULT_TITLE}</h3>
                <p className="sub">{settings.inquiry_subtitle?.trim() || DEFAULT_SUBTITLE}</p>

                <form onSubmit={submit} noValidate>
                    {fields}
                    <button className="btn btn-accent" style={{ width: '100%', marginTop: 4 }} type="submit" disabled={form.processing}>
                        {buttonText}
                    </button>
                </form>
            </div>
        );
    }

    return (
        <form onSubmit={submit} noValidate>
            {fields}

            <button className="btn btn-accent btn-lg" style={{ width: '100%' }} type="submit" disabled={form.processing}>
                {buttonText}
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round">
                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4z" />
                </svg>
            </button>
        </form>
    );
}
