<?php

namespace App\Filament\Resources\Partners\Schemas;

use App\Support\ImageOptimizer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Име')
                    ->required()
                    ->maxLength(190),

                FileUpload::make('logo')
                    ->label('Лого')
                    ->image()
                    ->disk('public')
                    ->directory('partners')
                    ->maxSize(12288)
                    ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => ImageOptimizer::store($file, 'public', 'partners', 480))
                    ->helperText('Свива се автоматично до 480 px по широчина; прозрачният фон се запазва.'),

                TextInput::make('url')
                    ->label('Уебсайт')
                    ->url()
                    ->maxLength(190),

                TextInput::make('sort_order')
                    ->label('Подредба')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_published')
                    ->label('Показва се')
                    ->default(true),
            ]);
    }
}
