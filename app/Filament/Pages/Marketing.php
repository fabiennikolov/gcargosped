<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\GoogleAds;
use App\Support\Marketing as MarketingTags;
use BackedEnum;
use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Every tracking and verification code on the public site, in one place:
 * Google Ads, Google Analytics, Tag Manager, Meta pixels, LinkedIn, and the
 * meta tags that prove the domain is ours to Google and Meta.
 *
 * Each field takes whatever the vendor puts on screen — the whole <script>
 * block, the meta tag, the bare ID — and tidies itself up to the ID when the
 * cursor leaves it. Empty = that tag is not on the page at all.
 */
class Marketing extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Маркетинг';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.marketing';

    /** @var array<string, mixed> */
    public array $data = [];

    /** Every key this page owns; anything else in the table is left alone. */
    private const KEYS = [
        'google_ads_id', 'google_ads_label',
        'ga4_id', 'gtm_id',
        'meta_pixel_ids',
        'linkedin_partner_id', 'linkedin_lead_id',
        'google_site_verification', 'facebook_domain_verification',
        'consent_text', 'privacy_url',
    ];

    public function getTitle(): string|Htmlable
    {
        return 'Маркетинг и проследяване';
    }

    public function mount(): void
    {
        $stored = Setting::map();

        $this->form->fill(
            collect(self::KEYS)
                ->mapWithKeys(fn (string $key) => [
                    $key => $key === 'meta_pixel_ids'
                        ? implode("\n", MarketingTags::splitList($stored[$key] ?? null))
                        : ($stored[$key] ?? ''),
                ])
                ->all(),
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                /*
                 * The two halves of the Google Ads tag. The ID loads the tag
                 * on every page; the label names the one conversion the site
                 * reports, and it is reported only when a form is actually
                 * sent — never on a page load.
                 */
                Section::make('Google Ads')
                    ->description('Поставете кода от Google Ads и запазете — това е всичко. Празно ID = Google Ads не се зарежда.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('google_ads_id')
                            ->label('ID на Google тага')
                            ->placeholder('AW-123456789')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                [$id, $label] = GoogleAds::extract($state);

                                if ($id === null) {
                                    return;
                                }

                                $set('google_ads_id', $id);

                                // The whole snippet was pasted into the first
                                // box, which is what someone with both codes on
                                // one screen does. The label was in it.
                                if ($label !== null) {
                                    $set('google_ads_label', $label);
                                }
                            })
                            ->regex(fn (mixed $state): ?string => filled($state) ? GoogleAds::ID_INPUT_PATTERN : null)
                            ->validationMessages(['regex' => 'ID-то изглежда като AW-123456789.'])
                            ->helperText('Google Ads → Цели → Google таг. Може да се постави целият код — полетата се подреждат сами.'),

                        TextInput::make('google_ads_label')
                            ->label('Етикет на реализацията')
                            ->placeholder('AbC-D_efGhIjKlM')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                [$id, $label] = GoogleAds::extract($state);

                                if ($label !== null) {
                                    $set('google_ads_label', $label);
                                }

                                // An ID came along with it and there is none
                                // yet: fill it rather than make them find it
                                // again. An ID already entered stays.
                                if ($id !== null && blank($get('google_ads_id'))) {
                                    $set('google_ads_id', $id);
                                }
                            })
                            ->regex(fn (mixed $state): ?string => filled($state) ? GoogleAds::LABEL_INPUT_PATTERN : null)
                            ->validationMessages(['regex' => 'Етикетът е частта след наклонената черта в send_to.'])
                            ->helperText('От фрагмента за събитие на реализацията. Празно = тагът се зарежда, но нищо не се отчита като реализация.'),
                    ]),

                Section::make('Google Analytics и Tag Manager')
                    ->description('Празно поле = съответният код не се зарежда.')
                    ->columns(2)
                    ->schema([
                        self::idField('ga4_id', MarketingTags::extractGa4(...), MarketingTags::GA4_PATTERN)
                            ->label('Google Analytics 4 — ID за измерване')
                            ->placeholder('G-ABCDE12345')
                            ->validationMessages(['regex' => 'ID-то изглежда като G-ABCDE12345.'])
                            ->helperText('Analytics → Администриране → Потоци от данни → уеб потока. При изпратено запитване се отчита събитие generate_lead.'),

                        self::idField('gtm_id', MarketingTags::extractGtm(...), MarketingTags::GTM_PATTERN)
                            ->label('Google Tag Manager — контейнер')
                            ->placeholder('GTM-ABC1234')
                            ->validationMessages(['regex' => 'ID-то изглежда като GTM-ABC1234.'])
                            ->helperText('Само ако ползвате GTM. Не добавяйте в контейнера същите тагове, които са попълнени тук, иначе всичко се брои двойно. При запитване в dataLayer отива събитие inquiry_sent.'),
                    ]),

                /*
                 * Several pixels, because an agency running the ads often
                 * brings its own dataset alongside the client's. All of them
                 * are initialised and every event goes to every one.
                 */
                Section::make('Meta (Facebook / Instagram) Pixel')
                    ->description('Празно = пикселът не се зарежда.')
                    ->schema([
                        Textarea::make('meta_pixel_ids')
                            ->label('ID на пикселите')
                            ->rows(3)
                            ->placeholder("1234567890123456\n6543210987654321")
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $ids = MarketingTags::extractMetaPixels($state);

                                if ($ids !== []) {
                                    $set('meta_pixel_ids', implode("\n", $ids));
                                }
                            })
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                $ids = MarketingTags::splitList((string) $value);

                                foreach ($ids as $id) {
                                    if (preg_match(MarketingTags::META_PIXEL_PATTERN, $id) !== 1) {
                                        $fail("„{$id}“ не прилича на ID на пиксел — то е само цифри, обикновено 15–16.");

                                        return;
                                    }
                                }

                                if (count(array_unique($ids)) > MarketingTags::MAX_META_PIXELS) {
                                    $fail('Най-много '.MarketingTags::MAX_META_PIXELS.' пиксела.');
                                }
                            })
                            ->helperText('По един на ред. Events Manager → Източници на данни → ID на набора от данни. Може да се постави целият базов код. При изпратено запитване се отчита събитие Lead.'),
                    ]),

                Section::make('LinkedIn Insight Tag')
                    ->description('Празно ID на партньор = LinkedIn не се зарежда.')
                    ->columns(2)
                    ->schema([
                        self::idField('linkedin_partner_id', MarketingTags::extractLinkedinPartner(...), MarketingTags::LINKEDIN_PARTNER_PATTERN)
                            ->label('ID на партньор')
                            ->placeholder('1234567')
                            ->validationMessages(['regex' => 'ID-то на партньор е само цифри.'])
                            ->helperText('Campaign Manager → Analyze → Insight Tag. Може да се постави целият код.'),

                        self::idField('linkedin_lead_id', MarketingTags::extractLinkedinConversion(...), MarketingTags::LINKEDIN_CONVERSION_PATTERN)
                            ->label('ID на реализацията за запитване')
                            ->placeholder('12345678')
                            ->validationMessages(['regex' => 'ID-то на реализацията е само цифри.'])
                            ->helperText('По желание. Отчита се, когато запитване е изпратено.'),
                    ]),

                /*
                 * Shown only while at least one tracking tag above is filled
                 * in: with none, there is nothing to ask consent for.
                 */
                Section::make('Банер за бисквитки')
                    ->description('Показва се, само ако е попълнен поне един таг за проследяване по-горе. Докато посетителят не натисне „Приемам“, Google работи без бисквитки, а Meta и LinkedIn изобщо не се зареждат.')
                    ->schema([
                        Textarea::make('consent_text')
                            ->label('Текст на банера')
                            ->rows(3)
                            ->maxLength(400)
                            ->placeholder('Използваме бисквитки, за да измерваме посещенията и рекламите си. Можете да откажете — сайтът работи и без тях.')
                            ->helperText('Празно = показва се текстът в сивото.'),

                        TextInput::make('privacy_url')
                            ->label('Линк към политиката за поверителност')
                            ->url()
                            ->placeholder('https://gcargosped.com/…')
                            ->helperText('По желание. Показва се като „Научете повече“ в банера.'),
                    ]),

                Section::make('Потвърждаване на домейна')
                    ->description('Кодовете, с които Google Search Console и Meta проверяват, че сайтът е ваш.')
                    ->columns(2)
                    ->schema([
                        self::idField('google_site_verification', MarketingTags::extractGoogleVerification(...), MarketingTags::GOOGLE_VERIFICATION_PATTERN)
                            ->label('Google Search Console')
                            ->placeholder('<meta name="google-site-verification" content="…">')
                            ->validationMessages(['regex' => 'Поставете HTML тага от Search Console или само стойността в content.'])
                            ->helperText('Search Console → Добавяне на собственост → HTML таг.'),

                        self::idField('facebook_domain_verification', MarketingTags::extractFacebookVerification(...), MarketingTags::FACEBOOK_VERIFICATION_PATTERN)
                            ->label('Meta (Facebook)')
                            ->placeholder('<meta name="facebook-domain-verification" content="…">')
                            ->validationMessages(['regex' => 'Поставете мета тага от Meta Business Suite или само стойността в content.'])
                            ->helperText('Business Settings → Brand Safety → Domains → Meta-tag verification.'),
                    ]),
            ]);
    }

    /**
     * A single-ID field: tidies a pasted snippet down to the ID on blur, and
     * refuses to save anything that is still not the vendor's shape.
     *
     * @param  Closure(?string): ?string  $extract
     */
    private static function idField(string $name, Closure $extract, string $pattern): TextInput
    {
        return TextInput::make($name)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, ?string $state) use ($name, $extract): void {
                $id = $extract($state);

                if ($id !== null) {
                    $set($name, $id);
                }
            })
            ->regex(fn (mixed $state): ?string => filled($state) ? $pattern : null);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (self::KEYS as $key) {
            $value = trim((string) ($state[$key] ?? ''));

            if ($key === 'meta_pixel_ids') {
                $value = implode("\n", array_unique(MarketingTags::splitList($value)));
            }

            Setting::put($key, $value === '' ? null : $value);
        }

        Notification::make()
            ->title('Настройките за маркетинг са запазени')
            ->success()
            ->send();
    }
}
