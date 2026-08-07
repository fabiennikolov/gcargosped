<?php

namespace App\Filament\Resources\Posts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image_url')
                    ->label('Корица')
                    ->height(44),

                TextColumn::make('title')
                    ->label('Заглавие')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label('Категория')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_published')
                    ->label('Публикувана')
                    ->boolean(),

                TextColumn::make('published_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y')
                    ->sortable(),

                TextColumn::make('read_minutes')
                    ->label('Мин')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->preload(),

                TernaryFilter::make('is_published')
                    ->label('Статус')
                    ->trueLabel('Публикувани')
                    ->falseLabel('Чернови')
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
