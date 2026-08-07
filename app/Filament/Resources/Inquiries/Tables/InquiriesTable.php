<?php

namespace App\Filament\Resources\Inquiries\Tables;

use App\Models\Inquiry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Получено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Име')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('email')
                    ->label('Имейл')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('cargo_type')
                    ->label('Товар / услуга')
                    ->toggleable(),

                TextColumn::make('origin')
                    ->label('От')
                    ->toggleable(),

                TextColumn::make('destination')
                    ->label('До')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Inquiry::STATUSES[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'new' => 'warning',
                        'in_progress', 'quoted' => 'info',
                        'won' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('source')
                    ->label('Източник')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(Inquiry::STATUSES),

                SelectFilter::make('source')
                    ->label('Източник')
                    ->options([
                        'offer' => 'Поискай оферта',
                        'contact' => 'Контакти',
                        'service' => 'Страница на услуга',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Отвори'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Изтрий'),
                ]),
            ]);
    }
}
