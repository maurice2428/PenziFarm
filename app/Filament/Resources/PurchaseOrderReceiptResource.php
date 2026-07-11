<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderReceiptResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReceipt;
use App\Services\Procurement\ProcurementLifecycleService;
use App\Services\Procurement\PurchaseReceivingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Throwable;

class PurchaseOrderReceiptResource extends Resource
{
    protected static ?string $model = PurchaseOrderReceipt::class;
    protected static ?string $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'GRN(s)';
    protected static ?string $modelLabel = 'Goods Received Note';
    protected static ?string $pluralModelLabel = 'Goods Received Notes';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'procurement/goods-received-notes';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view goods received notes')
            || auth()->user()?->hasAnyRole(['Admin', 'Administrator'])
            || false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create goods received notes')
            || auth()->user()?->hasAnyRole(['Admin', 'Administrator'])
            || false;
    }

    public static function canDelete($record): bool
    {
        return (
            auth()->user()?->can('delete goods received notes')
            || auth()->user()?->hasAnyRole(['Admin', 'Administrator'])
        ) && app(ProcurementLifecycleService::class)
            ->canDeleteReceipt($record);
    }

    public static function getEloquentQuery(): Builder
    {
        /*
         * Archived GRNs remain available through the Archive Status
         * filter and can be restored for audit review.
         */
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Form $form): Form
    {
        $requestPurchaseOrderId =
            request()->integer('purchase_order_id') ?: null;

        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Goods Received Note')
                    ->description(
                        'Receive ordered items, record supplier rejections, '
                        . 'and post accepted quantities to Stock In.'
                    )
                    ->icon('heroicon-o-truck')
                    ->columns([
                        'default' => 1,
                        'md' => 6,
                        'xl' => 12,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('receipt_no')
                            ->label('GRN No.')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 3,
                            ]),

                        Forms\Components\Select::make('purchase_order_id')
                            ->label('Purchase Order')
                            ->options(
                                fn (): array =>
                                    static::purchaseOrderOptions()
                            )
                            ->default($requestPurchaseOrderId)
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(
                                fn (Set $set, ?int $state) =>
                                    $set(
                                        'items',
                                        static::receiptRows($state)
                                    )
                            )
                            ->prefixIcon(
                                'heroicon-o-clipboard-document-list'
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 4,
                                'xl' => 5,
                            ]),

                        Forms\Components\DatePicker::make('received_date')
                            ->label('Received Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 4,
                            ]),

                        Forms\Components\TextInput::make('delivery_note_no')
                            ->label('Delivery Note No.')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-document-text')
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 4,
                            ]),

                        Forms\Components\TextInput::make('supplier_invoice_no')
                            ->label('Supplier Invoice No.')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-receipt-percent')
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 4,
                            ]),

                        Forms\Components\Placeholder::make('po_position')
                            ->label('Receiving Position')
                            ->content(function (Get $get): string {
                                $order = PurchaseOrder::query()
                                    ->find($get('purchase_order_id'));

                                if (! $order) {
                                    return 'Select a purchase order to load receiving items.';
                                }

                                return count(
                                    app(PurchaseReceivingService::class)
                                        ->formRows($order)
                                ) . ' purchase line(s) still require receiving or closure.';
                            })
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 4,
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Receiving Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Items Received')
                    ->description(
                        'Accepted quantity increases stock. Rejected quantity '
                        . 'never increases stock. Replacement keeps the PO '
                        . 'balance open; credit, refund, or authorised short '
                        . 'delivery closes the rejected balance.'
                    )
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Receiving Lines')
                            ->dehydrated(false)
                            ->default(
                                fn (): array =>
                                    static::receiptRows(
                                        $requestPurchaseOrderId
                                    )
                            )
                            ->defaultItems(0)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(
                                fn (array $state): string =>
                                    $state['item_name'] ?? 'Receiving Line'
                            )
                            ->columns([
                                'default' => 1,
                                'md' => 6,
                                'xl' => 12,
                            ])
                            ->schema([
                                Forms\Components\Hidden::make(
                                    'purchase_order_item_id'
                                ),
                                Forms\Components\Hidden::make(
                                    'inventory_item_id'
                                ),

                                Forms\Components\TextInput::make('item_name')
                                    ->label('Stock Item')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 6,
                                        'xl' => 6,
                                    ]),

                                Forms\Components\TextInput::make(
                                    'ordered_quantity'
                                )
                                    ->label('Ordered')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 2,
                                    ]),

                                Forms\Components\TextInput::make(
                                    'previously_received_quantity'
                                )
                                    ->label('Previously Received')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 2,
                                    ]),

                                Forms\Components\TextInput::make(
                                    'remaining_quantity'
                                )
                                    ->label('Remaining')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 2,
                                    ]),

                                Forms\Components\TextInput::make(
                                    'accepted_quantity'
                                )
                                    ->label('Accepted / Stock In')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(
                                        fn (Get $get): float =>
                                            (float) (
                                                $get(
                                                    'remaining_quantity'
                                                ) ?? 0
                                            )
                                    )
                                    ->required()
                                    ->prefixIcon(
                                        'heroicon-o-arrow-down-tray'
                                    )
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Forms\Components\TextInput::make(
                                    'rejected_quantity'
                                )
                                    ->label('Rejected')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(
                                        fn (Get $get): float =>
                                            (float) (
                                                $get(
                                                    'remaining_quantity'
                                                ) ?? 0
                                            )
                                    )
                                    ->live(onBlur: true)
                                    ->prefixIcon('heroicon-o-x-circle')
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->prefix('KES')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Forms\Components\TextInput::make('batch_number')
                                    ->label('Batch Number')
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Expiry Date')
                                    ->native(false)
                                    ->prefixIcon(
                                        'heroicon-o-calendar-days'
                                    )
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Forms\Components\Select::make(
                                    'rejection_disposition'
                                )
                                    ->label('Rejected Quantity Action')
                                    ->native(false)
                                    ->options([
                                        'awaiting_replacement' =>
                                            'Awaiting Supplier Replacement',
                                        'returned_to_supplier' =>
                                            'Returned to Supplier',
                                        'supplier_credit_note' =>
                                            'Supplier Credit Note',
                                        'supplier_refund' =>
                                            'Supplier Refund',
                                        'accepted_short_delivery' =>
                                            'Close as Authorised Short Delivery',
                                    ])
                                    ->visible(
                                        fn (Get $get): bool =>
                                            (float) (
                                                $get('rejected_quantity') ?? 0
                                            ) > 0
                                    )
                                    ->required(
                                        fn (Get $get): bool =>
                                            (float) (
                                                $get('rejected_quantity') ?? 0
                                            ) > 0
                                    )
                                    ->helperText(
                                        'Replacement/return keeps the line open. '
                                        . 'Credit, refund and short delivery '
                                        . 'close the rejected balance.'
                                    )
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 4,
                                    ]),

                                Forms\Components\TextInput::make(
                                    'rejection_reference'
                                )
                                    ->label('Credit / Return Reference')
                                    ->visible(
                                        fn (Get $get): bool =>
                                            (float) (
                                                $get('rejected_quantity') ?? 0
                                            ) > 0
                                    )
                                    ->required(
                                        fn (Get $get): bool =>
                                            (float) (
                                                $get('rejected_quantity') ?? 0
                                            ) > 0
                                            && in_array(
                                                $get(
                                                    'rejection_disposition'
                                                ),
                                                [
                                                    'returned_to_supplier',
                                                    'supplier_credit_note',
                                                    'supplier_refund',
                                                ],
                                                true
                                            )
                                    )
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 5,
                                    ]),

                                Forms\Components\Textarea::make(
                                    'rejection_reason'
                                )
                                    ->label('Rejection Reason')
                                    ->rows(2)
                                    ->visible(
                                        fn (Get $get): bool =>
                                            (float) (
                                                $get('rejected_quantity') ?? 0
                                            ) > 0
                                    )
                                    ->required(
                                        fn (Get $get): bool =>
                                            (float) (
                                                $get('rejected_quantity') ?? 0
                                            ) > 0
                                    )
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 6,
                                        'xl' => 6,
                                    ]),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Line Notes')
                                    ->rows(2)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 6,
                                        'xl' => 6,
                                    ]),
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

                Tables\Columns\TextColumn::make(
                    'purchaseOrder.purchase_order_number'
                )
                    ->label('PO No.')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-clipboard-document-list'),

                Tables\Columns\TextColumn::make(
                    'purchaseOrder.supplier.company_name'
                )
                    ->label('Supplier')
                    ->searchable()
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('received_date')
                    ->label('Received')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),

                Tables\Columns\TextColumn::make(
                    'total_accepted_quantity'
                )
                    ->label('Accepted Qty')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make(
                    'total_rejected_quantity'
                )
                    ->label('Rejected Qty')
                    ->badge()
                    ->color(
                        fn ($state): string =>
                            (float) $state > 0 ? 'danger' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('total_received_value')
                    ->label('Received Value')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string =>
                            str($state)->replace('_', ' ')->title()
                    )
                    ->color(
                        fn ($state): string => match ($state) {
                            'received' => 'success',
                            'partial' => 'warning',
                            'reversed' => 'danger',
                            'cancelled' => 'gray',
                            default => 'gray',
                        }
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'received' => 'Received',
                        'partial' => 'Partial',
                        'reversed' => 'Reversed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\TrashedFilter::make()
                    ->label('Archive Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl'),

                Tables\Actions\Action::make('reverseReceipt')
                    ->label('Reverse GRN')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(
                        fn (PurchaseOrderReceipt $record): bool =>
                            $record->can_be_reversed
                            && (
                                auth()->user()?->can(
                                    'reverse goods received notes'
                                )
                                || auth()->user()?->can(
                                    'delete goods received notes'
                                )
                                || auth()->user()?->hasAnyRole([
                                    'Administrator',
                                    'Admin',
                                ])
                            )
                    )
                    ->requiresConfirmation()
                    ->modalDescription(
                        'Accepted quantities will be posted as Stock Out '
                        . 'reversals. The action is blocked if the stock is '
                        . 'no longer available.'
                    )
                    ->form([
                        Forms\Components\Textarea::make('reversal_reason')
                            ->required()
                            ->minLength(8)
                            ->rows(3),
                    ])
                    ->action(
                        function (
                            PurchaseOrderReceipt $record,
                            array $data
                        ): void {
                            app(ProcurementLifecycleService::class)
                                ->reverseReceipt(
                                    $record,
                                    $data['reversal_reason']
                                );

                            Notification::make()
                                ->success()
                                ->title('GRN reversed')
                                ->body(
                                    'Stock and any linked accounting journal '
                                    . 'were reversed.'
                                )
                                ->send();
                        }
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('Archive Reversed')
                    ->visible(
                        fn (PurchaseOrderReceipt $record): bool =>
                            static::canDelete($record)
                    )
                    ->requiresConfirmation(),

                Tables\Actions\RestoreAction::make()
                    ->label('Restore Archived GRN')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('reverseSelected')
                    ->label('Reverse Selected GRNs')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can(
                                'reverse goods received notes'
                            )
                            || auth()->user()?->can(
                                'delete goods received notes'
                            )
                            || auth()->user()?->hasAnyRole([
                                'Administrator',
                                'Admin',
                            ])
                    )
                    ->form([
                        Forms\Components\Textarea::make('reversal_reason')
                            ->required()
                            ->minLength(8)
                            ->rows(3),
                    ])
                    ->action(
                        function (
                            Collection $records,
                            array $data
                        ): void {
                            $reversed = 0;
                            $skipped = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                if (! $record->can_be_reversed) {
                                    $skipped++;
                                    continue;
                                }

                                try {
                                    app(
                                        ProcurementLifecycleService::class
                                    )->reverseReceipt(
                                        $record,
                                        $data['reversal_reason']
                                    );
                                    $reversed++;
                                } catch (Throwable) {
                                    /*
                                     * Common cause: some of the received
                                     * stock has already been issued, so an
                                     * offsetting Stock Out cannot be posted.
                                     */
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title("{$reversed} GRN(s) reversed")
                                ->body(
                                    "{$skipped} were protected; "
                                    . "{$failed} could not be reversed, "
                                    . 'usually because stock is no longer '
                                    . 'available.'
                                )
                                ->color(
                                    $failed > 0
                                        ? 'warning'
                                        : 'success'
                                )
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('archiveReversed')
                    ->label('Archive Reversed GRNs')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can(
                                'delete goods received notes'
                            )
                            || auth()->user()?->hasAnyRole([
                                'Administrator',
                                'Admin',
                            ])
                    )
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $archived = 0;
                        $skipped = 0;

                        foreach ($records as $record) {
                            if (! $record->can_be_deleted_safely) {
                                $skipped++;
                                continue;
                            }

                            $record->delete();
                            $archived++;
                        }

                        Notification::make()
                            ->success()
                            ->title("{$archived} GRN(s) archived")
                            ->body(
                                "{$skipped} active GRN(s) were retained."
                            )
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\RestoreBulkAction::make()
                    ->label('Restore Archived GRNs')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success'),
            ]);
    }

    public static function purchaseOrderOptions(): array
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'items.receiptItems'])
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('id')
            ->get()
            ->filter(
                fn (PurchaseOrder $order): bool =>
                    app(PurchaseReceivingService::class)
                        ->hasRemaining($order)
            )
            ->mapWithKeys(function (PurchaseOrder $order): array {
                $supplier = $order->supplier?->company_name
                    ?? 'Unknown Supplier';

                return [
                    $order->id =>
                        $order->purchase_order_number
                        . ' | '
                        . $supplier
                        . ' | '
                        . $order->status,
                ];
            })
            ->all();
    }

    public static function receiptRows(?int $purchaseOrderId): array
    {
        if (! $purchaseOrderId) {
            return [];
        }

        $order = PurchaseOrder::query()->find($purchaseOrderId);

        return $order
            ? app(PurchaseReceivingService::class)->formRows($order)
            : [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrderReceipts::route('/'),
            'create' => Pages\CreatePurchaseOrderReceipt::route('/create'),
        ];
    }
}
