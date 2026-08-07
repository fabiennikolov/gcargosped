<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Съдържание')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Заглавие')
                            ->required()
                            ->maxLength(190)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                // Only auto-fill on create: changing a slug later
                                // would break a URL Google already has indexed.
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('URL адрес (slug)')
                            ->required()
                            ->maxLength(190)
                            ->unique(ignoreRecord: true)
                            ->helperText('Внимание: промяна разваля вече индексирани в Google линкове.'),

                        Textarea::make('subtitle')
                            ->label('Кратко описание')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Textarea::make('body')
                            ->label('Пълен текст')
                            ->rows(12)
                            ->columnSpanFull()
                            ->helperText('Празен ред разделя абзаците.'),
                    ]),

                Section::make('Изображение')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Снимка')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('services')
                            ->maxSize(4096)
                            ->helperText('Препоръчително съотношение 4:3, WebP или JPG.'),
                    ]),

                Section::make('Показване')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_main')
                            ->label('Основна услуга')
                            ->helperText('Основните са на /service/…, останалите на /sub-services/…'),

                        Toggle::make('is_published')
                            ->label('Публикувана')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Подредба')
                            ->numeric()
                            ->default(0)
                            ->helperText('По-малко число = по-напред.'),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('SEO заглавие')
                            ->maxLength(190)
                            ->placeholder('Ако е празно, ползва се заглавието.'),

                        Textarea::make('seo_description')
                            ->label('SEO описание')
                            ->rows(3)
                            ->maxLength(300)
                            ->placeholder('Ако е празно, ползва се краткото описание.'),
                    ]),
            ]);
    }
}
