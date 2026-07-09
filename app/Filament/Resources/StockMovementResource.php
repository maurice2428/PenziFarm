<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Movements';

    protected static ?string $modelLabel = 'Stock Movement';

    protected static ?string $pluralModelLabel = 'Stock Movements';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
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
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('movement_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('movement_no_display')
                    ->label('Movement No.')
                    ->state(fn (StockMovement $record): string => $record->movement_no_display)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('movement_no', 'like', "%{$search}%");
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('movement_no', $direction);
                    })
                    ->weight('bold')
                    ->badge()
                    ->color(fn (StockMovement $record): string => filled($record->movement_no) ? 'success' : 'gray')
                    ->icon(fn (StockMovement $record): string => filled($record->movement_no)
                        ? 'heroicon-o-hashtag'
                        : 'heroicon-o-minus-circle'
                    ),

                Tables\Columns\TextColumn::make('movement_date')
                    ->label('Movement Date')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('N/A')
                    ->icon('heroicon-o-calendar-days'),

                Tables\Columns\TextColumn::make('item_name_display')
                    ->label('Item')
                    ->state(fn (StockMovement $record): string => $record->item_name_display)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('inventoryItem', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->weight('bold')
                    ->badge()
                    ->color(fn (StockMovement $record): string => $record->inventory_item_id ? 'info' : 'gray')
                    ->icon('heroicon-o-cube'),

                Tables\Columns\TextColumn::make('direction_label')
                    ->label('Direction')
                    ->badge()
                    ->color(fn (StockMovement $record): string => match ($record->direction) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (StockMovement $record): string => match ($record->direction) {
                        'in' => 'heroicon-o-arrow-down-tray',
                        'out' => 'heroicon-o-arrow-up-tray',
                        default => 'heroicon-o-adjustments-horizontal',
                    }),

                Tables\Columns\TextColumn::make('type_label')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('type', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('source_label')
                    ->label('Source')
                    ->badge()
                    ->color('gray')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('source', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('signed_quantity')
                    ->label('Quantity')
                    ->badge()
                    ->color(fn (StockMovement $record): string => match ($record->direction) {
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('KES')
                    ->sortable()
                    ->placeholder('KES 0.00'),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('KES')
                    ->sortable()
                    ->placeholder('KES 0.00'),

                Tables\Columns\TextColumn::make('reference_label')
                    ->label('Reference')
                    ->state(fn (StockMovement $record): string => $record->reference_label)
                    ->badge()
                    ->color(fn (StockMovement $record): string =>
                        $record->reference_label === 'N/A' ? 'gray' : 'warning'
                    )
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('movement_no', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%")
                            ->orWhere('notes', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('batch_display')
                    ->label('Batch')
                    ->state(fn (StockMovement $record): string => $record->batch_display)
                    ->badge()
                    ->color(fn (StockMovement $record): string => filled($record->batch_number) ? 'info' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiry')
                    ->date('d M Y')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('notes_display')
                    ->label('Notes')
                    ->state(fn (StockMovement $record): string => $record->notes_display)
                    ->limit(45)
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('direction')
                    ->label('Direction')
                    ->options([
                        'in' => 'Stock In',
                        'out' => 'Stock Out',
                        'adjustment' => 'Adjustment',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Movement Type')
                    ->options([
                        'purchase_receipt' => 'Purchase Receipt',
                        'animal_feeding' => 'Animal Feeding',
                        'vet_treatment' => 'Vet Treatment',
                        'crop_input' => 'Crop Input',
                        'adjustment' => 'Adjustment',
                        'legacy' => 'Legacy',
                        'manual' => 'Manual',
                    ]),

                Tables\Filters\Filter::make('movement_date')
                    ->label('Movement Date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From')
                            ->native(false),

                        Forms\Components\DatePicker::make('to')
                            ->label('To')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('movement_date', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('movement_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-arrows-right-left')
            ->emptyStateHeading('No stock movements yet')
            ->emptyStateDescription('Stock movements will appear automatically from procurement receiving, animal feeding, vet treatment, crop input use, and adjustments.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
        ];
    }
}
