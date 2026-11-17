<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\InquiryForms;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

/**
 * The two public inquiry forms, field by field (see App\Support\InquiryForms).
 */
class InquiryFormsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Форми';

    protected static ?string $slug = 'forms';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.site-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function getTitle(): string|Htmlable
    {
        return 'Форми за запитване';
    }

    public function mount(): void
    {
        $this->fillFromSettings();
    }

    private function fillFromSettings(): void
    {
        $state = [];

        foreach (InquiryForms::VARIANTS as $variant) {
            $state[$variant] = collect(InquiryForms::rows($variant))
                ->mapWithKeys(fn (array $row) => [(string) Str::uuid() => $row])
                ->all();
            $state["{$variant}_button"] = (string) Setting::get("form_{$variant}_button");
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('forms')
                    ->tabs([
                        Tab::make('Пълна форма (Контакти)')
                            ->schema(self::formFields('offer', 'Изпрати запитване')),

                        Tab::make('Кратка форма (начална страница и услуги)')
                            ->schema(self::formFields('lead', 'Свържете се с мен')),
                    ])
                    ->persistTabInQueryString(),
            ]);
    }

    /** @return array<int, mixed> */
    private static function formFields(string $variant, string $defaultButton): array
    {
        return [
            TextInput::make("{$variant}_button")
                ->label('Текст на бутона')
                ->placeholder($defaultButton)
                ->maxLength(60)
                ->helperText('Празно = „'.$defaultButton.'“.'),

            Repeater::make($variant)
                ->label('Полета')
                ->helperText('Подреждат се с влачене. „Име“ и „Телефон“ са задължителни и не могат да се махнат. Отговорите на новите полета се виждат в запитването в админа и в имейла.')
                ->schema([
                    // Keeps a new field's posted name stable across saves.
                    Hidden::make('id'),

                    Select::make('field')
                        ->label('Поле')
                        ->options(self::fieldOptions())
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                            if ($state && $state !== 'custom' && blank($get('label'))) {
                                $set('label', InquiryForms::BUILTIN[$state]['label']);
                            }
                        })
                        ->disableOptionWhen(fn (string $value, Get $get) => $value !== 'custom'
                            && $value !== $get('field')
                            && collect($get("../../{$variant}"))->pluck('field')->contains($value)),

                    Select::make('type')
                        ->label('Вид на новото поле')
                        ->options(InquiryForms::CUSTOM_TYPES)
                        ->required(fn (Get $get) => $get('field') === 'custom')
                        ->visible(fn (Get $get) => $get('field') === 'custom')
                        ->live(),

                    TextInput::make('label')
                        ->label('Надпис')
                        ->required()
                        ->maxLength(120),

                    TextInput::make('placeholder')
                        ->label('Подсказка в полето')
                        ->maxLength(160)
                        ->visible(fn (Get $get) => ! in_array(self::typeOf($get), ['date', 'checkbox'], true)),

                    TextInput::make('help')
                        ->label('Пояснение под полето')
                        ->maxLength(300)
                        ->columnSpanFull(),

                    TagsInput::make('options')
                        ->label(fn (Get $get) => $get('field') === 'cargo_packaging' ? 'Предложения (може да се пише и друго)' : 'Възможности за избор')
                        ->placeholder('Напишете и натиснете Enter')
                        ->helperText(fn (Get $get) => $get('field') === 'cargo_type' ? 'Накрая винаги има „Друго…“ със свободен текст.' : null)
                        ->required(fn (Get $get) => $get('field') === 'custom' && $get('type') === 'select')
                        ->visible(fn (Get $get) => in_array($get('field'), InquiryForms::BUILTIN_WITH_OPTIONS, true)
                            || ($get('field') === 'custom' && $get('type') === 'select'))
                        ->reorderable()
                        ->columnSpanFull(),

                    Toggle::make('required')
                        ->label('Задължително')
                        ->disabled(fn (Get $get) => in_array($get('field'), InquiryForms::CORE, true))
                        ->dehydrated(),

                    Toggle::make('half')
                        ->label('Половин ширина (две полета на ред)'),
                ])
                ->columns(2)
                ->itemLabel(fn (array $state): ?string => self::itemLabel($state))
                ->addActionLabel('Добави поле')
                ->defaultItems(0)
                ->reorderable()
                ->collapsible()
                ->collapsed()
                ->cloneable()
                ->deletable()
                ->minItems(2),
        ];
    }

    /** @return array<string, string> */
    private static function fieldOptions(): array
    {
        $options = ['custom' => '＋ Ново поле (кръщавате го вие)'];

        foreach (InquiryForms::BUILTIN as $key => $field) {
            $options[$key] = 'Стандартно: '.$field['label'];
        }

        return $options;
    }

    private static function typeOf(Get $get): ?string
    {
        $field = $get('field');

        return $field === 'custom' ? $get('type') : (InquiryForms::BUILTIN[$field]['type'] ?? null);
    }

    /** @param array<string, mixed> $state */
    private static function itemLabel(array $state): ?string
    {
        $label = trim((string) ($state['label'] ?? ''));

        if ($label === '') {
            return null;
        }

        $marks = array_filter([
            ($state['field'] ?? '') === 'custom' ? (InquiryForms::CUSTOM_TYPES[$state['type'] ?? ''] ?? null) : 'стандартно',
            ! empty($state['required']) || in_array($state['field'] ?? '', InquiryForms::CORE, true) ? 'задължително' : null,
            ! empty($state['half']) ? '½' : null,
        ]);

        return $label.' — '.implode(', ', $marks);
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label('Върни стандартните форми')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('И двете форми се връщат такива, каквито бяха първоначално. Новите полета се махат; вече получените запитвания не се променят.')
                ->action(function () {
                    foreach (InquiryForms::VARIANTS as $variant) {
                        Setting::put("form_{$variant}", null);
                        Setting::put("form_{$variant}_button", null);
                    }

                    $this->fillFromSettings();

                    Notification::make()->title('Формите са върнати')->success()->send();
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (InquiryForms::VARIANTS as $variant) {
            $rows = InquiryForms::normalize(array_values((array) ($state[$variant] ?? [])));

            Setting::put("form_{$variant}", json_encode($rows, JSON_UNESCAPED_UNICODE));
            Setting::put("form_{$variant}_button", trim((string) ($state["{$variant}_button"] ?? '')) ?: null);
        }

        $this->fillFromSettings();

        Notification::make()
            ->title('Формите са запазени')
            ->body('Промените вече се виждат на сайта.')
            ->success()
            ->send();
    }
}
