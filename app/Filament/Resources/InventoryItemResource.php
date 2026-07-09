<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryItemResource\Pages;
use App\Models\InventoryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Stock Items';
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Stock Item')
                ->description('This is the actual stock ledger item used in procurement and deductions.')
                ->icon('heroicon-o-cube')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->prefixIcon('heroicon-o-tag'),

                    Forms\Components\Select::make('category')
                        ->required()
                        ->options([
                            'vaccine' => 'Vaccine',
                            'dewormer' => 'Dewormer',
                            'dip' => 'Dipping Chemical',
                            'treatment' => 'Treatment Drug',
                            'feed' => 'Feed',
                            'chemical' => 'Chemical',
                            'equipment' => 'Equipment',
                        ])
                        ->prefixIcon('heroicon-o-squares-2x2'),

                    Forms\Components\TextInput::make('unit')
                        ->default('ml')
                        ->required()
                        ->prefixIcon('heroicon-o-scale'),

                    Forms\Components\TextInput::make('opening_stock')
                        ->numeric()
                        ->default(0)
                        ->prefixIcon('heroicon-o-archive-box'),

                    Forms\Components\TextInput::make('reorder_level')
                        ->numeric()
                        ->default(0)
                        ->prefixIcon('heroicon-o-bell-alert'),

                    Forms\Components\TextInput::make('order_level')
                        ->numeric()
                        ->default(0)
                        ->prefixIcon('heroicon-o-shopping-cart'),

                    Forms\Components\TextInput::make('unit_cost')
                        ->numeric()
                        ->default(0)
                        ->prefixIcon('heroicon-o-banknotes'),

                    Forms\Components\DatePicker::make('expiry_date')
                        ->prefixIcon('heroicon-o-calendar-days'),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true),

                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-cube'),

                Tables\Columns\TextColumn::make('category_label')
                    ->badge()
                    ->color(fn ($record) => match ($record->category) {
                        'vaccine' => 'success',
                        'dewormer' => 'warning',
                        'dip' => 'info',
                        'treatment' => 'danger',
                        'feed' => 'gray',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->badge()
                    ->color(fn ($record) => $record->is_low_stock ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2) . ' ' . $record->unit),

                Tables\Columns\TextColumn::make('stock_value')
                    ->money('KES'),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->date('d M Y')
                    ->color(fn ($record) => $record->expiry_date?->isPast() ? 'danger' : 'gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth('5xl')
                    ->icon('heroicon-o-pencil-square'),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryItems::route('/'),
        ];
    }
}
