<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Models\InventoryItem;
use App\Models\StockAdjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Adjustments';

    protected static ?string $modelLabel = 'Stock Adjustment';

    protected static ?string $pluralModelLabel = 'Stock Adjustments';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view stock adjustments') ?? false;
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create stock adjustments') ?? false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Stock Adjustment')
                    ->description('Use this only for stock corrections, damaged stock, expired stock, or physical count corrections.')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('adjustment_no')
                            ->label('Adjustment No.')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->columnSpan(3),

                        Forms\Components\DatePicker::make('adjustment_date')
                            ->label('Adjustment Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\Select::make('reason')
                            ->label('Reason')
                            ->default('manual_correction')
                            ->native(false)
                            ->required()
                            ->options([
                                'manual_correction' => 'Manual Correction',
                                'damaged_stock' => 'Damaged Stock',
                                'expired_stock' => 'Expired Stock',
                                'stock_count' => 'Physical Stock Count',
                                'opening_balance_correction' => 'Opening Balance Correction',
                                'other' => 'Other',
                            ])
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('notes')
                            ->label('Adjustment Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Adjustment Items')
                    ->description('Stock In increases inventory. Stock Out reduces inventory.')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Items')
                            ->dehydrated(false)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(12)
                            ->schema([
                                Forms\Components\Select::make('inventory_item_id')
                                    ->label('Stock Item')
                                    ->options(fn (): array => InventoryItem::query()
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (InventoryItem $item): array => [
                                            $item->id => $item->name . ' | Stock: ' . number_format((float) $item->current_stock, 3) . ' ' . $item->unit,
                                        ])
                                        ->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                                        $item = InventoryItem::query()->find($state);

                                        if (! $item) {
                                            return;
                                        }

                                        $set('unit', $item->unit);
                                        $set('unit_cost', number_format((float) $item->unit_cost, 2, '.', ''));
                                        $set('current_stock', number_format((float) $item->current_stock, 3, '.', ''));
                                    })
                                    ->columnSpan(4),

                                Forms\Components\Select::make('direction')
                                    ->label('Direction')
                                    ->native(false)
                                    ->required()
                                    ->options([
                                        'in' => 'Stock In',
                                        'out' => 'Stock Out',
                                    ])
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('unit')
                                    ->label('Unit')
                                    ->readOnly()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->prefix('KES')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('current_stock')
                                    ->label('Current')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Line Notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('adjustment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('adjustment_no')
                    ->label('Adjustment No.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag'),

                Tables\Columns\TextColumn::make('adjustment_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason_label')
                    ->label('Reason')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_in_quantity')
                    ->label('Stock In')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('total_out_quantity')
                    ->label('Stock Out')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Value')
                    ->money('KES'),

                Tables\Columns\TextColumn::make('adjustedBy.name')
                    ->label('Adjusted By')
                    ->placeholder('N/A'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('6xl')
                    ->visible(fn (): bool => auth()->user()?->can('view stock adjustments') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make(
                    'exportSelected'
                )
                    ->label('Export Selected')
                    ->icon(
                        'heroicon-o-arrow-down-tray'
                    )
                    ->color('gray')
                    ->visible(fn (): bool => auth()->user()?->can('export stock adjustments') ?? false)
                    ->action(
                        function (
                            Collection $records
                        ) {
                            return response()
                                ->streamDownload(
                                    function () use (
                                        $records
                                    ): void {
                                        $handle = fopen(
                                            'php://output',
                                            'wb'
                                        );

                                        fputcsv($handle, [
                                            'Adjustment No.',
                                            'Date',
                                            'Reason',
                                            'Stock In',
                                            'Stock Out',
                                            'Value',
                                            'Adjusted By',
                                            'Notes',
                                        ]);

                                        foreach ($records as $record) {
                                            fputcsv($handle, [
                                                $record
                                                    ->adjustment_no,
                                                $record
                                                    ->adjustment_date
                                                    ?->format(
                                                        'Y-m-d'
                                                    ),
                                                $record
                                                    ->reason_label,
                                                (float) $record
                                                    ->total_in_quantity,
                                                (float) $record
                                                    ->total_out_quantity,
                                                (float) $record
                                                    ->total_value,
                                                $record
                                                    ->adjustedBy
                                                    ?->name,
                                                $record->notes,
                                            ]);
                                        }

                                        fclose($handle);
                                    },
                                    'stock-adjustments-selected-'
                                    . now(
                                        'Africa/Nairobi'
                                    )->format(
                                        'Ymd_His'
                                    )
                                    . '.csv',
                                    [
                                        'Content-Type' =>
                                            'text/csv',
                                    ]
                                );
                        }
                    )
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
        ];
    }
}
