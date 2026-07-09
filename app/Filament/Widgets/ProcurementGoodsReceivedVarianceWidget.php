<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrderItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProcurementGoodsReceivedVarianceWidget extends BaseWidget
{
    protected static ?string $heading = 'Goods Received Variance';

    protected static ?string $description = 'Shows items where ordered quantity is higher than received quantity.';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public static function canView(): bool
    {
        return auth()->user()?->can('view procurement dashboard') ?? false;
    }

    protected function range(): array
    {
        return [
            $this->dateFrom ?: now('Africa/Nairobi')->startOfMonth()->toDateString(),
            $this->dateTo ?: now('Africa/Nairobi')->endOfMonth()->toDateString(),
        ];
    }

    public function table(Table $table): Table
    {
        [$from, $to] = $this->range();

        return $table
            ->query(
                PurchaseOrderItem::query()
                    ->with(['purchaseOrder.supplier', 'inventoryItem', 'healthProduct'])
                    ->whereNull('deleted_at')
                    ->whereRaw('quantity_received < quantity_ordered')
                    ->whereHas('purchaseOrder', function ($query) use ($from, $to) {
                        $query
                            ->whereNull('deleted_at')
                            ->whereBetween('order_date', [$from, $to]);
                    })
                    ->orderByDesc('id')
            )
            ->recordUrl(fn (PurchaseOrderItem $record): string =>
                PurchaseOrderResource::getUrl('edit', ['record' => $record->purchase_order_id])
            )
            ->columns([
                Tables\Columns\TextColumn::make('purchaseOrder.purchase_order_number')
                    ->label('PO')
                    ->weight('bold')
                    ->searchable()
                    ->icon('heroicon-o-clipboard-document-list'),

                Tables\Columns\TextColumn::make('purchaseOrder.supplier.company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('item_name')
                    ->label('Item')
                    ->state(fn (PurchaseOrderItem $record) =>
                        $record->healthProduct?->name ?? $record->inventoryItem?->name ?? '-'
                    )
                    ->icon('heroicon-o-cube'),

                Tables\Columns\TextColumn::make('quantity_ordered')
                    ->label('Ordered')
                    ->formatStateUsing(fn ($state, PurchaseOrderItem $record) =>
                        number_format((float) $state, 2) . ' ' . ($record->inventoryItem?->unit ?? '')
                    ),

                Tables\Columns\TextColumn::make('quantity_received')
                    ->label('Received')
                    ->formatStateUsing(fn ($state, PurchaseOrderItem $record) =>
                        number_format((float) $state, 2) . ' ' . ($record->inventoryItem?->unit ?? '')
                    )
                    ->color('warning'),

                Tables\Columns\TextColumn::make('variance')
                    ->label('Pending')
                    ->state(fn (PurchaseOrderItem $record) =>
                        number_format(max(0, (float) $record->quantity_ordered - (float) $record->quantity_received), 2)
                        . ' ' . ($record->inventoryItem?->unit ?? '')
                    )
                    ->badge()
                    ->color('danger'),
            ])
            ->paginated([5, 10, 25]);
    }
}
