<?php

namespace App\Filament\Resources\Services\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Снимка')
                    ->height(48),

                TextColumn::make('title')
                    ->label('Заглавие')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('slug')
                    ->label('URL')
                    ->searchable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_main')
                    ->label('Основна')
                    ->boolean(),

                IconColumn::make('is_published')
                    ->label('Публикувана')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('Ред')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Обновена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_main')
                    ->label('Вид')
                    ->trueLabel('Основни услуги')
                    ->falseLabel('Специализирани превози')
                    ->placeholder('Всички'),

                TernaryFilter::make('is_published')
                    ->label('Статус')
                    ->trueLabel('Публикувани')
                    ->falseLabel('Скрити')
                    ->placeholder('Всички'),
            ])
            ->recordActions([
                EditAction::make()->label('Редактирай'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Изтрий'),
                ]),
            ]);
    }
}
