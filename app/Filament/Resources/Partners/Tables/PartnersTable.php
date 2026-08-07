<?php

namespace App\Filament\Resources\Partners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('logo_url')->label('Лого')->height(38),
                TextColumn::make('name')->label('Име')->searchable()->sortable(),
                IconColumn::make('is_published')->label('Показва се')->boolean(),
                TextColumn::make('sort_order')->label('Ред')->numeric()->sortable(),
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
