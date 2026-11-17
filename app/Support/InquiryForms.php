<?php

namespace App\Support;

use App\Models\Inquiry;
use App\Models\Setting;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The public inquiry forms as the admin arranges them (Форми in the admin).
 *
 * There are two forms. `lead` is the compact card in the home page hero and on
 * every service page; `offer` is the full quote form on /contact. Each one is a
 * list of fields, stored as JSON in the settings table under `form_<variant>`:
 *
 *   - a standard field is one of BUILTIN — it fills a column of its own on the
 *     inquiry, and only its wording, width and whether it is required can
 *     change. Name and phone cannot be removed or made optional: an inquiry
 *     nobody can call back is not an inquiry.
 *   - a new field is one of CUSTOM_TYPES, named by the admin. Its answer goes
 *     into `inquiries.extra` together with the label it had when it was sent,
 *     so renaming or deleting the field later does not orphan old answers.
 *
 * Nothing saved yet = DEFAULTS, which are the forms exactly as they were
 * before they became editable.
 */
class InquiryForms
{
    public const VARIANTS = ['lead', 'offer'];

    /** Which form each `source` came from. */
    public const SOURCE_VARIANT = [
        'offer' => 'lead',
        'service' => 'lead',
        'contact' => 'offer',
    ];

    /** Fields that are always on the form and always required. */
    public const CORE = ['name', 'phone'];

    /**
     * Standard fields: what the admin sees in the picker, and the input type
     * the front end draws.
     *
     * @var array<string, array{label: string, type: string}>
     */
    public const BUILTIN = [
        'name' => ['label' => 'Име', 'type' => 'text'],
        'phone' => ['label' => 'Телефон', 'type' => 'tel'],
        'email' => ['label' => 'Имейл', 'type' => 'email'],
        'origin' => ['label' => 'Начална дестинация', 'type' => 'text'],
        'destination' => ['label' => 'Крайна дестинация', 'type' => 'text'],
        'cargo_type' => ['label' => 'Тип товар', 'type' => 'select'],
        'cargo_quantity' => ['label' => 'Брой', 'type' => 'number'],
        'cargo_packaging' => ['label' => 'Опаковка', 'type' => 'text'],
        'cargo_dimensions' => ['label' => 'Размери', 'type' => 'text'],
        'cargo_weight' => ['label' => 'Общо тегло', 'type' => 'text'],
        'loading_date' => ['label' => 'Дата на товарене', 'type' => 'date'],
        'frequency' => ['label' => 'Еднократен / редовен', 'type' => 'select'],
        'message' => ['label' => 'Допълнителна информация', 'type' => 'textarea'],
    ];

    /** @var array<string, string> */
    public const CUSTOM_TYPES = [
        'text' => 'Текст (един ред)',
        'textarea' => 'Текст (няколко реда)',
        'number' => 'Число',
        'date' => 'Дата',
        'select' => 'Падащо меню',
        'checkbox' => 'Отметка (да / не)',
    ];

    /** Standard fields whose list of choices the admin edits. */
    public const BUILTIN_WITH_OPTIONS = ['cargo_type', 'cargo_packaging'];

    public const CARGO_TYPES = [
        'Кола / Автомобил', 'Палет', 'Ремарке', 'Кашон / Каса',
        'Машина / Оборудване', 'Групажен товар', 'Метали (гондола)',
    ];

    public const PACKAGING = ['Палети', 'Кашони', 'Каси', 'Бъчви', 'Чували', 'Биг бег', 'Рулони', 'Без опаковка'];

    /**
     * The form as an editable row list: what the admin page shows and saves.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function rows(string $variant): array
    {
        $raw = json_decode((string) Setting::get("form_{$variant}"), true);

        $rows = is_array($raw) ? self::normalize($raw) : [];

        return $rows === [] ? self::defaults($variant) : $rows;
    }

    /**
     * The form as the public site draws it.
     *
     * @return array{fields: array<int, array<string, mixed>>, button: ?string}
     */
    public static function forFrontend(string $variant): array
    {
        return [
            'fields' => array_map(fn (array $row) => [
                'key' => self::inputKey($row),
                'builtin' => $row['field'] !== 'custom',
                'type' => $row['field'] === 'custom' ? $row['type'] : self::BUILTIN[$row['field']]['type'],
                'label' => $row['label'],
                'placeholder' => $row['placeholder'],
                'help' => $row['help'],
                'required' => $row['required'],
                'half' => $row['half'],
                'options' => $row['options'],
            ], self::rows($variant)),
            'button' => trim((string) Setting::get("form_{$variant}_button")) ?: null,
        ];
    }

    /**
     * Validation rules for a submission from the given source, built from the
     * fields that form actually shows.
     *
     * @return array<string, mixed>
     */
    public static function rules(string $source): array
    {
        $rules = [];

        foreach (self::rows(self::SOURCE_VARIANT[$source] ?? 'offer') as $row) {
            $presence = $row['required'] ? 'required' : 'nullable';

            if ($row['field'] === 'custom') {
                $key = 'extra.'.self::inputKey($row);

                $rules[$key] = match ($row['type']) {
                    'number' => [$presence, 'numeric', 'max:1000000000'],
                    'date' => [$presence, 'date'],
                    'select' => [$presence, 'string', Rule::in($row['options'])],
                    'checkbox' => $row['required'] ? ['accepted'] : ['nullable', 'boolean'],
                    'textarea' => [$presence, 'string', 'max:4000'],
                    default => [$presence, 'string', 'max:500'],
                };

                continue;
            }

            $rules[$row['field']] = [$presence, ...self::builtinRules($row['field'])];
        }

        return $rules;
    }

    /**
     * The answers to the admin's own fields, with the label each had when it
     * was sent. Empty answers are left out.
     *
     * @param  array<string, mixed>  $extra
     * @return array<int, array{label: string, value: string}>
     */
    public static function extraAnswers(string $source, array $extra): array
    {
        $answers = [];

        foreach (self::rows(self::SOURCE_VARIANT[$source] ?? 'offer') as $row) {
            if ($row['field'] !== 'custom') {
                continue;
            }

            $value = $extra[self::inputKey($row)] ?? null;

            if ($row['type'] === 'checkbox') {
                $value = filter_var($value, FILTER_VALIDATE_BOOL) ? 'Да' : null;
            }

            if (blank($value)) {
                continue;
            }

            $answers[] = ['label' => $row['label'], 'value' => trim((string) $value)];
        }

        return $answers;
    }

    /**
     * The standard fields a form shows — anything else posted is ignored, so
     * a field the admin removed cannot be filled by a crafted request.
     *
     * @return array<int, string>
     */
    public static function builtinKeys(string $source): array
    {
        return collect(self::rows(self::SOURCE_VARIANT[$source] ?? 'offer'))
            ->pluck('field')
            ->reject(fn (string $field) => $field === 'custom')
            ->values()
            ->all();
    }

    /**
     * Turn whatever the admin page (or the database) holds into clean rows,
     * dropping anything that cannot be drawn: unknown fields, a standard field
     * listed twice, a dropdown with nothing to choose. Name and phone are put
     * back at the top if they went missing.
     *
     * @param  array<int|string, mixed>  $raw
     * @return array<int, array<string, mixed>>
     */
    public static function normalize(array $raw): array
    {
        $rows = [];
        $seen = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $field = (string) ($item['field'] ?? '');
            $isCustom = $field === 'custom';

            if (! $isCustom && ! isset(self::BUILTIN[$field])) {
                continue;
            }

            if (! $isCustom && isset($seen[$field])) {
                continue;
            }

            $type = $isCustom ? (string) ($item['type'] ?? '') : self::BUILTIN[$field]['type'];

            if ($isCustom && ! isset(self::CUSTOM_TYPES[$type])) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));

            if ($label === '') {
                $label = $isCustom ? '' : self::BUILTIN[$field]['label'];
            }

            if ($label === '') {
                continue;
            }

            $options = array_values(array_unique(array_filter(
                array_map(fn ($option) => trim((string) $option), (array) ($item['options'] ?? [])),
                fn (string $option) => $option !== '',
            )));

            if ($isCustom && $type === 'select' && $options === []) {
                continue;
            }

            $id = (string) ($item['id'] ?? '');
            if ($isCustom && ! preg_match('/^[a-z0-9]{6,32}$/', $id)) {
                $id = Str::lower(Str::random(10));
            }

            $rows[] = [
                'id' => $isCustom ? $id : $field,
                'field' => $field,
                'type' => $isCustom ? $type : null,
                'label' => mb_substr($label, 0, 120),
                'placeholder' => mb_substr(trim((string) ($item['placeholder'] ?? '')), 0, 160),
                'help' => mb_substr(trim((string) ($item['help'] ?? '')), 0, 300),
                'required' => in_array($field, self::CORE, true) || (bool) ($item['required'] ?? false),
                'half' => (bool) ($item['half'] ?? false),
                'options' => $isCustom
                    ? ($type === 'select' ? $options : [])
                    : (in_array($field, self::BUILTIN_WITH_OPTIONS, true) ? $options : []),
            ];

            if (! $isCustom) {
                $seen[$field] = true;
            }
        }

        foreach (array_reverse(self::CORE) as $core) {
            if (! isset($seen[$core])) {
                array_unshift($rows, self::builtinRow($core, ['required' => true]));
            }
        }

        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    public static function defaults(string $variant): array
    {
        if ($variant === 'lead') {
            return [
                self::builtinRow('name', ['placeholder' => 'Вашето име', 'required' => true]),
                self::builtinRow('phone', ['placeholder' => '+359 ...', 'required' => true]),
                self::builtinRow('email', ['placeholder' => 'you@company.com']),
            ];
        }

        return [
            self::builtinRow('name', ['placeholder' => 'Име и фамилия', 'required' => true, 'half' => true]),
            self::builtinRow('phone', ['placeholder' => '+359 ...', 'required' => true, 'half' => true]),
            self::builtinRow('email', ['placeholder' => 'you@company.com', 'required' => true]),
            self::builtinRow('origin', ['placeholder' => 'Град / адрес на товарене']),
            self::builtinRow('destination', ['placeholder' => 'Град / адрес на доставка']),
            self::builtinRow('cargo_type', ['placeholder' => 'Изберете тип товар…', 'options' => self::CARGO_TYPES]),
            self::builtinRow('cargo_quantity', ['placeholder' => 'напр. 4', 'half' => true]),
            self::builtinRow('cargo_packaging', ['placeholder' => 'Палети, кашони…', 'half' => true, 'options' => self::PACKAGING]),
            self::builtinRow('cargo_dimensions', ['label' => 'Размери (Д × Ш × В)', 'placeholder' => '120 × 80 × 150 см', 'half' => true]),
            self::builtinRow('cargo_weight', ['placeholder' => 'напр. 1200 кг', 'half' => true]),
            self::builtinRow('loading_date', ['half' => true]),
            self::builtinRow('frequency', ['label' => 'Транспортът е', 'placeholder' => 'Изберете…', 'half' => true]),
            self::builtinRow('message', [
                'placeholder' => 'Специални изисквания, температурен режим, ADR…',
                'help' => 'По желание — колкото повече детайли, толкова по-точна оферта.',
            ]),
        ];
    }

    /**
     * The name a field's value is posted under: the column for a standard
     * field, `x<id>` inside `extra` for one of the admin's own.
     *
     * @param  array<string, mixed>  $row
     */
    private static function inputKey(array $row): string
    {
        return $row['field'] === 'custom' ? 'x'.$row['id'] : $row['field'];
    }

    /** @return array<int, mixed> */
    private static function builtinRules(string $field): array
    {
        return match ($field) {
            'name' => ['string', 'max:120'],
            'phone' => ['string', 'max:40'],
            'email' => ['email:rfc', 'max:180'],
            'cargo_type' => ['string', 'max:120'],
            'cargo_quantity' => ['integer', 'min:1', 'max:100000'],
            'cargo_packaging' => ['string', 'max:120'],
            'cargo_dimensions' => ['string', 'max:160'],
            'cargo_weight' => ['string', 'max:60'],
            'loading_date' => ['date'],
            'frequency' => [Rule::in(array_keys(Inquiry::FREQUENCIES))],
            'message' => ['string', 'max:4000'],
            default => ['string', 'max:160'],
        };
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private static function builtinRow(string $field, array $overrides = []): array
    {
        return [
            'id' => $field,
            'field' => $field,
            'type' => null,
            'label' => self::BUILTIN[$field]['label'],
            'placeholder' => '',
            'help' => '',
            'required' => false,
            'half' => false,
            'options' => [],
            ...$overrides,
        ];
    }
}
