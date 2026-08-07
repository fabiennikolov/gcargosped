<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

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
        'site_name', 'hero_title', 'hero_subtitle', 'hero_cta',
        'phone', 'phone_raw', 'email', 'address', 'working_hours',
        'facebook_url', 'linkedin_url', 'seo_title', 'seo_description',
    ];

    public function getTitle(): string|Htmlable
    {
        return 'Настройки на сайта';
    }

    public function mount(): void
    {
        $stored = Setting::map();

        $this->form->fill(
            collect(self::KEYS)->mapWithKeys(fn (string $key) => [$key => $stored[$key] ?? ''])->all(),
        );
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

                        Textarea::make('hero_subtitle')
                            ->label('Подзаглавие')
                            ->rows(3)
                            ->columnSpanFull(),
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
            Setting::put($key, $state[$key] ?? null);
        }

        Notification::make()
            ->title('Настройките са запазени')
            ->success()
            ->send();
    }
}
