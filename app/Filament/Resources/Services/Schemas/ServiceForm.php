<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Support\ImageOptimizer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                    ->description('Снимката се показва в картата на услугата и като фон в заглавието на страницата ѝ.')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Снимка')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('services')
                            ->maxSize(12288)
                            // Scaled and converted to WebP again on the server,
                            // so a file that skipped the editor is shrunk too.
                            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => ImageOptimizer::store($file, 'public', 'services', 1280, thumbWidth: 720))
                            /*
                             * Scaled down in the browser before it is uploaded.
                             * A photo taken on a phone is 4000px wide and
                             * several megabytes; the card it ends up in is
                             * never wider than 800 (1280 leaves room for a retina screen). Only the width is given, so
                             * the height follows the original proportions and
                             * nothing is cropped or letterboxed. The size limit
                             * is generous because it applies to the file the
                             * editor picks, before this shrinks it.
                             */
                            ->imageResizeTargetWidth('1280')
                            ->imageResizeUpscale(false)
                            ->helperText('Качете каквато и да е снимка — свива се автоматично до 1280 px по широчина. Най-добре изглежда в съотношение 4:3.'),

                        TextInput::make('image_alt')
                            ->label('ALT текст на снимката')
                            ->maxLength(190)
                            ->placeholder('Камион на Глобал Карго Спед товари палети')
                            ->helperText('Не се вижда никъде на сайта — чете се само от Google и от екранните четци. Празно = ползва се заглавието на услугата.'),
                    ]),

                Section::make('Показване')
                    ->columns(3)
                    ->schema([
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
