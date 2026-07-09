<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'Suppliers';
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view suppliers') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create suppliers') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit suppliers') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete suppliers') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Supplier Profile')
                ->description('Register vendors, agrovet suppliers, feed suppliers, transporters, and service providers.')
                ->icon('heroicon-o-building-storefront')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('company_name')
                        ->label('Supplier Name')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-building-office-2'),

                    Forms\Components\TextInput::make('contact_person')
                        ->label('Contact Person')
                        ->prefixIcon('heroicon-o-user'),

                    Forms\Components\TextInput::make('phone_primary')
                        ->label('Primary Phone')
                        ->tel()
                        ->prefixIcon('heroicon-o-phone'),

                    Forms\Components\TextInput::make('phone_secondary')
                        ->label('Secondary Phone')
                        ->tel()
                        ->prefixIcon('heroicon-o-device-phone-mobile'),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->prefixIcon('heroicon-o-envelope'),

                    Forms\Components\TextInput::make('kra_pin')
                        ->label('KRA PIN')
                        ->prefixIcon('heroicon-o-identification'),

                    Forms\Components\TextInput::make('physical_address')
                        ->label('Address')
                        ->columnSpanFull()
                        ->prefixIcon('heroicon-o-map-pin'),

                    Forms\Components\TextInput::make('bank_name')
                        ->label('Bank Name')
                        ->prefixIcon('heroicon-o-building-library'),

                    Forms\Components\TextInput::make('bank_account')
                        ->label('Bank Account')
                        ->prefixIcon('heroicon-o-credit-card'),

                    Forms\Components\Select::make('status')
                        ->default('active')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'blocked' => 'Blocked',
                        ])
                        ->prefixIcon('heroicon-o-check-badge'),

                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('company_name')
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable()
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('phone_primary')
                    ->label('Phone')
                    ->searchable()
                    ->icon('heroicon-o-phone'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'blocked' => 'danger',
                        default => 'gray',
                    })
                    ->icon('heroicon-o-check-badge'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth('5xl')
                    ->color('warning')
                    ->icon('heroicon-o-pencil-square'),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Supplier?')
                    ->modalDescription('This supplier will be soft deleted and retained for audit history.')
                    ->icon('heroicon-o-trash'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
        ];
    }
}
