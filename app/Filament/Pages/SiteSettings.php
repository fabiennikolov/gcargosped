<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\ImageOptimizer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * A single form over the key/value settings table — phone, e-mail, address and
 * the home page hero copy, which the public site reads on every request.
 */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Настройки на сайта';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.site-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    /** Every key this page owns; anything else in the table is left alone. */
    private const KEYS = [
        'site_name', 'logo_path', 'hero_title', 'hero_title_size', 'hero_subtitle', 'hero_cta', 'stats',
        'phone', 'phone_raw', 'email', 'address', 'working_hours', 'footer_tagline',
        'whatsapp_number', 'whatsapp_greeting', 'whatsapp_teaser', 'whatsapp_topics',
        'inquiry_title', 'inquiry_subtitle', 'inquiry_success',
        'facebook_url', 'linkedin_url', 'seo_title', 'seo_description',
    ];

    public function getTitle(): string|Htmlable
    {
        return 'Настройки на сайта';
    }

    /**
     * Settings that are edited as a list but stored as one JSON string, because
     * the settings table is key/value and holds strings only.
     */
    private const LIST_KEYS = ['whatsapp_topics'];

    /**
     * The same idea for repeaters whose rows are more than one field: stored as
     * one JSON string, edited as a list of rows.
     */
    private const ROW_KEYS = ['stats'];

    /** Columns each row of a ROW_KEYS repeater keeps, in order. */
    private const ROW_FIELDS = ['stats' => ['value', 'label']];

    public function mount(): void
    {
        $stored = Setting::map();

        $this->form->fill(
            collect(self::KEYS)
                ->mapWithKeys(fn (string $key) => [
                    $key => match (true) {
                        in_array($key, self::LIST_KEYS, true) => self::decodeList($stored[$key] ?? null),
                        in_array($key, self::ROW_KEYS, true) => self::decodeRows($key, $stored[$key] ?? null),
                        default => $stored[$key] ?? '',
                    },
                ])
                ->all(),
        );
    }

    /**
     * Read a list setting, accepting the newline-separated text this field used
     * before it became a repeater so an existing site keeps its options.
     *
     * @return array<int, string>
     */
    private static function decodeList(?string $raw): array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            return array_values(array_filter(array_map(
                fn ($item) => trim((string) $item),
                $decoded,
            ), fn (string $item) => $item !== ''));
        }

        return array_values(array_filter(array_map('trim', explode("\n", $raw))));
    }

    /**
     * Read a repeater setting back into rows, dropping anything that is not a
     * row of the expected shape — a hand-edited value in the database must not
     * be able to break the settings screen.
     *
     * @return array<int, array<string, string>>
     */
    private static function decodeRows(string $key, ?string $raw): array
    {
        $decoded = json_decode(trim((string) $raw), true);

        if (! is_array($decoded)) {
            return [];
        }

        $fields = self::ROW_FIELDS[$key];

        return array_values(array_filter(array_map(
            fn ($row) => is_array($row)
                ? collect($fields)->mapWithKeys(fn (string $field) => [
                    $field => trim((string) ($row[$field] ?? '')),
                ])->all()
                : null,
            $decoded,
        )));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Контакти')
                    ->description('Показват се в хедъра, футъра и формите за запитване.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')
                            ->label('Телефон (за показване)')
                            ->required()
                            ->placeholder('0877 415 141'),

                        TextInput::make('phone_raw')
                            ->label('Телефон (за набиране)')
                            ->required()
                            ->placeholder('+359877415141')
                            ->helperText('Международен формат — ползва се в tel: линковете.'),

                        TextInput::make('email')
                            ->label('Имейл')
                            ->email()
                            ->required(),

                        TextInput::make('address')
                            ->label('Адрес'),

                        TextInput::make('working_hours')
                            ->label('Работно време')
                            ->columnSpanFull(),
                    ]),

                /*
                 * The copy around the short form — the two lines above it and
                 * the thank-you after it. One set for every form on the site:
                 * the home page hero and each service page show the same pair,
                 * so a promise the office changes its mind about is changed in
                 * one place. Empty falls back to the wording in the component.
                 */
                Section::make('Форма за запитване')
                    ->description('Текстът над кратката форма и съобщението след изпращане. Празно поле = показва се текстът в сивото.')
                    ->schema([
                        TextInput::make('inquiry_title')
                            ->label('Заглавие над формата')
                            ->maxLength(80)
                            ->placeholder('Изпрати запитване'),

                        TextInput::make('inquiry_subtitle')
                            ->label('Ред под заглавието')
                            ->maxLength(160)
                            ->placeholder('Оставете данни и ще ви потърсим с оферта.')
                            ->helperText('Тук стоеше обещанието за отговор до 1 работен ден — сменя се свободно.'),

                        Textarea::make('inquiry_success')
                            ->label('Съобщение след изпращане')
                            ->rows(2)
                            ->maxLength(300)
                            ->placeholder('Благодарим! Ще се свържем с вас възможно най-скоро.'),
                    ]),

                /*
                 * The floating button reads these three. An empty number hides
                 * it entirely, so the section can be filled in later without
                 * shipping a half-configured widget to visitors.
                 */
                Section::make('WhatsApp бутон')
                    ->description('Зеленото кръгче долу вдясно. Празен номер = бутонът не се показва.')
                    ->schema([
                        TextInput::make('whatsapp_number')
                            ->label('WhatsApp номер')
                            ->tel()
                            ->placeholder('+359877415141'),

                        TextInput::make('whatsapp_greeting')
                            ->label('Надпис над опциите')
                            ->placeholder('Здравейте! С какво можем да помогнем?'),

                        TextInput::make('whatsapp_teaser')
                            ->label('Подканващо балонче')
                            ->placeholder('Имате въпрос? Пишете ни.'),

                        /*
                         * A repeater rather than a textarea: the options are a
                         * list, and a list edited as free text is one stray
                         * blank line away from an empty button in the menu.
                         * Simple mode keeps the stored shape a flat array of
                         * strings instead of a row of objects.
                         */
                        Repeater::make('whatsapp_topics')
                            ->label('Опции в менюто')
                            ->simple(
                                TextInput::make('topic')
                                    ->hiddenLabel()
                                    ->required()
                                    ->maxLength(60)
                                    ->placeholder('Оферта за превоз'),
                            )
                            ->addActionLabel('Добави опция')
                            ->reorderable()
                            ->defaultItems(0)
                            ->maxItems(6),
                    ]),

                /*
                 * The mark in the header and the footer. Stored as a path, the
                 * same as every other uploaded image; empty falls back to the
                 * file the original site shipped, so a half-finished change
                 * never leaves the site with no logo at all.
                 */
                Section::make('Лого')
                    ->description('Показва се в хедъра и във футъра. Празно = ползва се логото, с което сайтът е пуснат.')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Лого')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->maxSize(12288)
                            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => ImageOptimizer::store($file, 'public', 'branding', 400))
                            ->imagePreviewHeight('80')
                            ->helperText('PNG, WebP или SVG. Най-добре квадратно и поне 200×200 px — показва се на 62 px. По-големите снимки се свиват автоматично.'),
                    ]),

                Section::make('Начална страница')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')->label('Име на сайта'),
                        TextInput::make('hero_cta')->label('Текст на бутона'),

                        Textarea::make('hero_title')
                            ->label('Главно заглавие')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Позволен е HTML — <span class="hl">…</span> оцветява дума в зелено.'),

                        Select::make('hero_title_size')
                            ->label('Размер на главното заглавие')
                            ->options([
                                '70' => '70% — много по-малко',
                                '80' => '80%',
                                '90' => '90%',
                                '100' => '100% — както е по дизайн',
                                '110' => '110%',
                                '120' => '120% — по-голямо',
                            ])
                            ->placeholder('100% — както е по дизайн')
                            ->helperText('Важи и за компютър, и за телефон — заглавието се мащабира пропорционално.'),

                        Textarea::make('hero_subtitle')
                            ->label('Подзаглавие')
                            ->rows(3)
                            ->columnSpanFull(),

                        /*
                         * The dark band under the services. Four is what the
                         * layout is built for; fewer still line up, more would
                         * wrap into a second row of one.
                         */
                        Repeater::make('stats')
                            ->label('Числа в тъмната лента')
                            ->helperText('Празен списък = показват се числата по подразбиране.')
                            ->columns(2)
                            ->schema([
                                TextInput::make('value')
                                    ->label('Число')
                                    ->required()
                                    ->maxLength(12)
                                    ->placeholder('15+')
                                    ->helperText('Знакът след числото се оцветява — „15+“ показва зелено „+“.'),

                                TextInput::make('label')
                                    ->label('Надпис')
                                    ->required()
                                    ->maxLength(40)
                                    ->placeholder('години опит'),
                            ])
                            ->itemLabel(fn (array $state): ?string => trim(($state['value'] ?? '').' '.($state['label'] ?? '')) ?: null)
                            ->addActionLabel('Добави число')
                            ->reorderable()
                            ->collapsible()
                            ->defaultItems(0)
                            ->maxItems(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Футър')
                    ->description('Редът под логото в долния край на всяка страница.')
                    ->schema([
                        Textarea::make('footer_tagline')
                            ->label('Текст под логото')
                            ->rows(2)
                            ->maxLength(300)
                            ->placeholder('Вашият товар е наша грижа. Транспорт, складиране и логистика в България, Европа и Турция.'),
                    ]),

                Section::make('Социални мрежи')
                    ->columns(2)
                    ->schema([
                        TextInput::make('facebook_url')->label('Facebook')->url()->placeholder('https://…'),
                        TextInput::make('linkedin_url')->label('LinkedIn')->url()->placeholder('https://…'),
                    ])
                    ->description('Празно поле = иконата не се показва.'),

                Section::make('SEO по подразбиране')
                    ->collapsed()
                    ->schema([
                        TextInput::make('seo_title')->label('Заглавие на началната страница')->maxLength(190),
                        Textarea::make('seo_description')->label('Описание')->rows(3)->maxLength(300),
                    ]),
            ]);
    }

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Запази')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (self::KEYS as $key) {
            $value = $state[$key] ?? null;

            if (in_array($key, self::LIST_KEYS, true)) {
                $value = self::encodeList($value);
            }

            if (in_array($key, self::ROW_KEYS, true)) {
                $value = self::encodeRows($key, $value);
            }

            Setting::put($key, $value);
        }

        Notification::make()
            ->title('Настройките са запазени')
            ->success()
            ->send();
    }

    /**
     * Store a repeater's rows as one JSON array. An empty list is stored as
     * null rather than "[]" so the front end falls back to its defaults instead
     * of rendering a menu with no options in it.
     */
    private static function encodeList(mixed $value): ?string
    {
        $items = array_values(array_filter(
            array_map(fn ($item) => trim((string) $item), (array) $value),
            fn (string $item) => $item !== '',
        ));

        return $items === [] ? null : json_encode($items, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Store a multi-field repeater as one JSON array of rows. Rows with an
     * empty field are dropped rather than stored half-filled, and an empty
     * list is stored as null so the front end falls back to its defaults.
     */
    private static function encodeRows(string $key, mixed $value): ?string
    {
        $fields = self::ROW_FIELDS[$key];

        $rows = collect((array) $value)
            ->map(fn ($row) => collect($fields)
                ->mapWithKeys(fn (string $field) => [$field => trim((string) (((array) $row)[$field] ?? ''))])
                ->all())
            ->reject(fn (array $row) => in_array('', $row, true))
            ->values()
            ->all();

        return $rows === [] ? null : json_encode($rows, JSON_UNESCAPED_UNICODE);
    }
}
