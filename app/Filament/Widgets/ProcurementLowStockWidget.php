<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class ProcurementLowStockWidget extends BaseWidget
{
    protected static ?string $heading = 'Low Stock & Reorder Pressure';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public static function canView(): bool
    {
        return auth()->user()?->can('view procurement dashboard') ?? false;
    }

    protected function inventoryUrl(InventoryItem $record): string
    {
        try {
            return InventoryItemResource::getUrl('edit', ['record' => $record]);
        } catch (Throwable) {
            return InventoryItemResource::getUrl('index');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InventoryItem::query()
                    ->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->orderBy('name')
            )
            ->recordUrl(fn (InventoryItem $record): string => $this->inventoryUrl($record))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Stock Item')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-cube'),

                Tables\Columns\TextColumn::make('category_label')
                    ->label('Category')
                    ->badge()
                    ->sortable()
                    ->icon('heroicon-o-squares-2x2'),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->formatStateUsing(fn ($state, InventoryItem $record): string =>
                        number_format((float) $record->current_stock, 2) . ' ' . $record->unit
                    )
                    ->badge()
                    ->color(fn (InventoryItem $record): string =>
                        $record->is_low_stock ? 'danger' : 'success'
                    )
                    ->icon('heroicon-o-archive-box'),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder Level')
                    ->formatStateUsing(fn ($state, InventoryItem $record): string =>
                        number_format((float) $record->reorder_level, 2) . ' ' . $record->unit
                    )
                    ->icon('heroicon-o-bell-alert'),

                Tables\Columns\TextColumn::make('order_level')
                    ->label('Order Level')
                    ->formatStateUsing(fn ($state, InventoryItem $record): string =>
                        number_format((float) $record->order_level, 2) . ' ' . $record->unit
                    )
                    ->icon('heroicon-o-shopping-cart'),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('KES')
                    ->icon('heroicon-o-banknotes'),

                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Stock Value')
                    ->money('KES')
                    ->icon('heroicon-o-calculator'),

                Tables\Columns\TextColumn::make('reorder_advice')
                    ->label('Advice')
                    ->state(fn (InventoryItem $record): string =>
                        $record->is_low_stock ? 'Reorder now' : 'Stock okay'
                    )
                    ->badge()
                    ->color(fn (InventoryItem $record): string =>
                        $record->is_low_stock ? 'danger' : 'success'
                    )
                    ->icon(fn (InventoryItem $record): string =>
                        $record->is_low_stock ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'
                    ),
            ])
            ->filters([
                Tables\Filters\Filter::make('low_stock_only')
                    ->label('Low stock only')
                    ->query(function (Builder $query): Builder {
                        return $query->whereRaw("
                            (
                                opening_stock + COALESCE((
                                    SELECT SUM(quantity)
                                    FROM stock_movements
                                    WHERE stock_movements.inventory_item_id = inventory_items.id
                                    AND stock_movements.deleted_at IS NULL
                                ), 0)
                            ) <= reorder_level
                        ");
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open_inventory')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (InventoryItem $record): string => $this->inventoryUrl($record)),
            ])
            ->paginated([5, 10, 25]);
    }
}
