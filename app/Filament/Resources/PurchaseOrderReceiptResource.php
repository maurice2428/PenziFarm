<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderReceiptResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderReceipt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderReceiptResource extends Resource
{
    protected static ?string $model = PurchaseOrderReceipt::class;

    protected static ?string $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Goods Received Notes';

    protected static ?string $modelLabel = 'Goods Received Note';

    protected static ?string $pluralModelLabel = 'Goods Received Notes';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'procurement/goods-received-notes';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view goods received notes')
            || auth()->user()?->hasRole('Admin')
            || auth()->user()?->hasRole('Administrator')
            || false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create goods received notes')
            || auth()->user()?->hasRole('Admin')
            || auth()->user()?->hasRole('Administrator')
            || false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Goods Received Note')
                    ->description('Receive ordered items, capture rejected quantities, batch numbers, expiry dates, and automatically add accepted stock to inventory.')
                    ->icon('heroicon-o-truck')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('receipt_no')
                            ->label('GRN No.')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(3),

                        Forms\Components\Select::make('purchase_order_id')
                            ->label('Purchase Order')
                            ->options(fn (): array => static::purchaseOrderOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?int $state): void {
                                $set('items', static::receiptRows($state));
                            })
                            ->prefixIcon('heroicon-o-clipboard-document-list')
                            ->columnSpan(5),

                        Forms\Components\DatePicker::make('received_date')
                            ->label('Received Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('delivery_note_no')
                            ->label('Delivery Note No.')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-document-text')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('supplier_invoice_no')
                            ->label('Supplier Invoice No.')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-receipt-percent')
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('po_position')
                            ->label('Receiving Position')
                            ->content(function (Get $get): string {
                                $order = PurchaseOrder::query()
                                    ->with('items.receiptItems')
                                    ->find($get('purchase_order_id'));

                                if (! $order) {
                                    return 'Select a purchase order to load receiving items.';
                                }

                                $totalItems = $order->items->count();
                                $receivedItems = $order->items->filter(function ($item) {
                                    $accepted = (float) $item->receiptItems->sum('accepted_quantity');
                                    return $accepted >= (float) $item->quantity_ordered;
                                })->count();

                                return "{$receivedItems} of {$totalItems} item lines fully received.";
                            })
                            ->columnSpan(4),

                        Forms\Components\Textarea::make('notes')
                            ->label('Receiving Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Items Received')
                    ->description('Accepted quantity increases stock. Rejected quantity is recorded but does not increase stock.')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Receiving Lines')
                            ->dehydrated(false)
                            ->defaultItems(0)
                            ->columns(12)
                            ->schema([
                                Forms\Components\Hidden::make('purchase_order_item_id'),
                                Forms\Components\Hidden::make('inventory_item_id'),

                                Forms\Components\TextInput::make('item_name')
                                    ->label('Item')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('ordered_quantity')
                                    ->label('Ordered')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('previously_received_quantity')
                                    ->label('Prev. Received')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('remaining_quantity')
                                    ->label('Remaining')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('accepted_quantity')
                                    ->label('Accepted')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('rejected_quantity')
                                    ->label('Rejected')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->prefix('KES')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('batch_number')
                                    ->label('Batch No.')
                                    ->columnSpan(1),

                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Expiry')
                                    ->native(false)
                                    ->columnSpan(1),

                                Forms\Components\Textarea::make('rejection_reason')
                                    ->label('Rejection Reason')
                                    ->rows(2)
                                    ->columnSpan(6),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Line Notes')
                                    ->rows(2)
                                    ->columnSpan(6),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('received_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('receipt_no')
                    ->label('GRN No.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag'),

                Tables\Columns\TextColumn::make('purchaseOrder.purchase_order_number')
                    ->label('PO No.')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-clipboard-document-list'),

                Tables\Columns\TextColumn::make('purchaseOrder.supplier.company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('received_date')
                    ->label('Received')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),

                Tables\Columns\TextColumn::make('total_accepted_quantity')
                    ->label('Accepted Qty')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('total_rejected_quantity')
                    ->label('Rejected Qty')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('total_received_value')
                    ->label('Received Value')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => str($state)->replace('_', ' ')->title())
                    ->color(fn ($state): string => match ($state) {
                        'received' => 'success',
                        'partial' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function purchaseOrderOptions(): array
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'items.receiptItems'])
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('id')
            ->get()
            ->filter(function (PurchaseOrder $order): bool {
                return $order->items->contains(function ($item): bool {
                    $accepted = (float) $item->receiptItems->sum('accepted_quantity');
                    return $accepted < (float) $item->quantity_ordered;
                });
            })
            ->mapWithKeys(function (PurchaseOrder $order): array {
                $supplier = $order->supplier?->company_name
                    ?? $order->supplier?->name
                    ?? 'Unknown Supplier';

                return [
                    $order->id => "{$order->purchase_order_number} | {$supplier} | {$order->status}",
                ];
            })
            ->toArray();
    }

    public static function receiptRows(?int $purchaseOrderId): array
    {
        if (! $purchaseOrderId) {
            return [];
        }

        return PurchaseOrderItem::query()
            ->with(['inventoryItem', 'healthProduct', 'receiptItems'])
            ->where('purchase_order_id', $purchaseOrderId)
            ->get()
            ->map(function (PurchaseOrderItem $item): ?array {
                $accepted = (float) $item->receiptItems->sum('accepted_quantity');
                $ordered = (float) $item->quantity_ordered;
                $remaining = max(0, $ordered - $accepted);

                if ($remaining <= 0) {
                    return null;
                }

                $itemName = $item->inventoryItem?->name
                    ?? $item->healthProduct?->name
                    ?? 'Unknown Item';

                return [
                    'purchase_order_item_id' => $item->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'item_name' => $itemName,
                    'ordered_quantity' => number_format($ordered, 3, '.', ''),
                    'previously_received_quantity' => number_format($accepted, 3, '.', ''),
                    'remaining_quantity' => number_format($remaining, 3, '.', ''),
                    'accepted_quantity' => number_format($remaining, 3, '.', ''),
                    'rejected_quantity' => 0,
                    'unit_cost' => number_format((float) $item->unit_cost, 2, '.', ''),
                    'batch_number' => $item->batch_number,
                    'expiry_date' => $item->expiry_date?->toDateString(),
                    'rejection_reason' => null,
                    'notes' => null,
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrderReceipts::route('/'),
            'create' => Pages\CreatePurchaseOrderReceipt::route('/create'),
        ];
    }
}
