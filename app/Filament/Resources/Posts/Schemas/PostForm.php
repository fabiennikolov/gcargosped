<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use App\Support\Slug;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Публикация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Заглавие')
                            ->required()
                            ->maxLength(190)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Slug::make((string) $state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('URL адрес (slug)')
                            ->required()
                            ->maxLength(190)
                            ->unique(ignoreRecord: true),

                        Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label('Име')->required(),
                                TextInput::make('slug')->label('Slug')->required(),
                            ]),

                        TextInput::make('read_minutes')
                            ->label('Време за четене (мин)')
                            ->numeric()
                            ->default(3)
                            ->minValue(1),

                        Textarea::make('excerpt')
                            ->label('Кратко резюме')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Textarea::make('body')
                            ->label('Текст на публикацията')
                            ->rows(16)
                            ->columnSpanFull()
                            ->helperText('Празен ред разделя абзаците.'),
                    ]),

                Section::make('Изображение')
                    ->schema([
                        FileUpload::make('cover_image')
                            ->label('Корица')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('posts')
                            ->maxSize(4096),
                    ]),

                Section::make('Публикуване')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Публикувана'),

                        DateTimePicker::make('published_at')
                            ->label('Дата на публикуване')
                            ->seconds(false)
                            ->default(now())
                            ->helperText('Бъдеща дата = насрочена публикация.'),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->schema([
                        TextInput::make('seo_title')->label('SEO заглавие')->maxLength(190),
                        Textarea::make('seo_description')->label('SEO описание')->rows(3)->maxLength(300),
                    ]),
            ]);
    }
}
