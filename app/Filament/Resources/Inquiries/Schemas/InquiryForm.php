<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Models\Inquiry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Запитване')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Име')->required()->maxLength(120),
                        TextInput::make('phone')->label('Телефон')->tel()->required()->maxLength(40),
                        TextInput::make('email')->label('Имейл')->email()->maxLength(180),
                        TextInput::make('cargo_type')->label('Вид товар / услуга')->maxLength(120),
                        TextInput::make('origin')->label('Начална дестинация')->maxLength(160),
                        TextInput::make('destination')->label('Крайна дестинация')->maxLength(160),
                        TextInput::make('cargo_quantity')->label('Брой')->numeric()->minValue(1),
                        TextInput::make('cargo_packaging')->label('Опаковка')->maxLength(120),
                        TextInput::make('cargo_dimensions')->label('Размери')->maxLength(160),
                        TextInput::make('cargo_weight')->label('Общо тегло')->maxLength(60),
                        DatePicker::make('loading_date')->label('Дата на товарене')->native(false)->displayFormat('d.m.Y'),
                        Select::make('frequency')->label('Транспорт')->options(Inquiry::FREQUENCIES),

                        Textarea::make('message')
                            ->label('Съобщение')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('Обработка')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options(Inquiry::STATUSES)
                            ->default('new')
                            ->required(),

                        Select::make('source')
                            ->label('Източник')
                            ->options([
                                'offer' => 'Форма „Поискай оферта“',
                                'contact' => 'Страница Контакти',
                                'service' => 'Страница на услуга',
                            ])
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('admin_note')
                            ->label('Вътрешна бележка')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Вижда се само в администрацията.'),
                    ]),
            ]);
    }
}
