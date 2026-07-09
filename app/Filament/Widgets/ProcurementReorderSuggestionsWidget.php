<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\InventoryItem;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class ProcurementReorderSuggestionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Reorder Suggestions';

    protected static ?string $description = 'Items below reorder level with suggested purchase quantities.';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view procurement dashboard') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InventoryItem::query()
                    ->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->whereRaw('
                        (
                            opening_stock + COALESCE((
                                SELECT SUM(quantity)
                                FROM stock_movements
                                WHERE stock_movements.inventory_item_id = inventory_items.id
                                AND stock_movements.deleted_at IS NULL
                            ), 0)
                        ) <= reorder_level
                    ')
                    ->orderBy('name')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Item')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-o-cube'),
                Tables\Columns\TextColumn::make('category_label')
                    ->label('Category')
                    ->badge(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Current')
                    ->state(fn(InventoryItem $record) =>
                        number_format((float) $record->current_stock, 2) . ' ' . $record->unit)
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder')
                    ->formatStateUsing(fn($state, InventoryItem $record) =>
                        number_format((float) $state, 2) . ' ' . $record->unit),
                Tables\Columns\TextColumn::make('suggested_quantity')
                    ->label('Suggested Buy')
                    ->state(fn(InventoryItem $record) =>
                        number_format(max(0, (float) $record->order_level - (float) $record->current_stock), 2)
                        . ' ' . $record->unit)
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('estimated_cost')
                    ->label('Est. Cost')
                    ->state(fn(InventoryItem $record) =>
                        'KES ' . number_format(
                            max(0, (float) $record->order_level - (float) $record->current_stock) * (float) $record->unit_cost,
                            2
                        ))
                    ->color('warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('create_po')
                    ->label('Create PO')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn() => PurchaseOrderResource::getUrl('create')),
            ])
            ->paginated([5, 10, 25]);
    }
}
