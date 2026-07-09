<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\HealthProduct;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Models\Supplier;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Purchase Orders';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view purchase orders') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create purchase orders') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit purchase orders') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete purchase orders') ?? false;
    }

    protected static function normalizeItems(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn($item) => is_array($item))
            ->values()
            ->all();
    }

    protected static function calculateOrderTotals(
        array $items,
        float $orderDiscount = 0,
        float $otherCharges = 0,
        float $amountPaid = 0
    ): array {
        $subtotal = 0;
        $itemTax = 0;
        $itemDiscounts = 0;

        foreach (self::normalizeItems($items) as $item) {
            $quantity = (float) ($item['quantity_ordered'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $discount = (float) ($item['discount_amount'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineSubtotal = $quantity * $unitCost;
            $taxableAmount = max(0, $lineSubtotal - $discount);
            $lineTax = $taxableAmount * ($taxRate / 100);

            $subtotal += $lineSubtotal;
            $itemTax += $lineTax;
            $itemDiscounts += $discount;
        }

        $grandTotal = max(
            0,
            $subtotal + $itemTax + $otherCharges - $itemDiscounts - $orderDiscount
        );

        $amountPaid = min($amountPaid, $grandTotal);
        $balanceDue = max(0, $grandTotal - $amountPaid);

        return [
            'subtotal' => round($subtotal, 2),
            'item_tax' => round($itemTax, 2),
            'item_discounts' => round($itemDiscounts, 2),
            'grand_total' => round($grandTotal, 2),
            'amount_paid' => round($amountPaid, 2),
            'balance_due' => round($balanceDue, 2),
        ];
    }

    protected static function syncOrderTotals(Set $set, Get $get, bool $fromRepeaterItem = false): void
    {
        $prefix = $fromRepeaterItem ? '../../' : '';

        $items = $get($prefix . 'items') ?? [];

        $orderDiscount = (float) ($get($prefix . 'discount_amount') ?? 0);
        $otherCharges = (float) ($get($prefix . 'other_charges') ?? 0);

        $recordInitialPayment = (bool) ($get($prefix . 'record_initial_payment') ?? false);

        $amountPaid = $recordInitialPayment
            ? (float) ($get($prefix . 'initial_payment_amount') ?? 0)
            : (float) ($get($prefix . 'amount_paid') ?? 0);

        $totals = self::calculateOrderTotals(
            $items,
            $orderDiscount,
            $otherCharges,
            $amountPaid
        );

        $set($prefix . 'subtotal', $totals['subtotal']);
        $set($prefix . 'tax_amount', $totals['item_tax']);
        $set($prefix . 'grand_total', $totals['grand_total']);
        $set($prefix . 'amount_paid', $totals['amount_paid']);
        $set($prefix . 'balance_due', $totals['balance_due']);
    }

    protected static function syncLineTotals(Set $set, Get $get): void
    {
        $quantity = (float) ($get('quantity_ordered') ?? 0);
        $unitCost = (float) ($get('unit_cost') ?? 0);
        $discount = (float) ($get('discount_amount') ?? 0);
        $taxRate = (float) ($get('tax_rate') ?? 0);

        $lineSubtotal = $quantity * $unitCost;
        $taxableAmount = max(0, $lineSubtotal - $discount);
        $lineTax = $taxableAmount * ($taxRate / 100);
        $lineTotal = $taxableAmount + $lineTax;

        $set('line_subtotal', round($lineSubtotal, 2));
        $set('tax_amount', round($lineTax, 2));
        $set('line_total', round($lineTotal, 2));

        self::syncOrderTotals($set, $get, true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Supplier & Invoice Details')
                ->description('Create a supplier procurement invoice and track it from order to payment and receiving.')
                ->icon('heroicon-o-building-storefront')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('purchase_order_number')
                        ->label('PO Number')
                        ->readOnly()
                        ->prefixIcon('heroicon-o-hashtag'),
                    Forms\Components\TextInput::make('invoice_number')
                        ->label('System Invoice No.')
                        ->readOnly()
                        ->prefixIcon('heroicon-o-document-text'),
                    Forms\Components\TextInput::make('supplier_invoice_number')
                        ->label('Supplier Invoice No.')
                        ->helperText('Optional supplier document reference.')
                        ->prefixIcon('heroicon-o-document-duplicate'),
                    Forms\Components\Select::make('supplier_id')
                        ->label('Supplier')
                        ->options(fn() => Supplier::query()
                            ->where('status', 'active')
                            ->orderBy('company_name')
                            ->pluck('company_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->prefixIcon('heroicon-o-building-storefront'),
                    Forms\Components\DatePicker::make('order_date')
                        ->label('Order Date')
                        ->default(now('Africa/Nairobi'))
                        ->required()
                        ->prefixIcon('heroicon-o-calendar-days'),
                    Forms\Components\DatePicker::make('invoice_date')
                        ->label('Invoice Date')
                        ->default(now('Africa/Nairobi'))
                        ->prefixIcon('heroicon-o-calendar'),
                    Forms\Components\DatePicker::make('due_date')
                        ->label('Due Date')
                        ->prefixIcon('heroicon-o-clock'),
                    Forms\Components\DatePicker::make('expected_delivery_date')
                        ->label('Expected Delivery')
                        ->prefixIcon('heroicon-o-truck'),
                    Forms\Components\Select::make('status')
                        ->label('Order Status')
                        ->default('draft')
                        ->options([
                            'draft' => 'Draft',
                            'ordered' => 'Ordered',
                            'partially_received' => 'Partially Received',
                            'received' => 'Received',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required()
                        ->prefixIcon('heroicon-o-check-circle'),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Procurement Items')
                ->description('Each product can have its own quantity, unit cost, item discount, and tax rate.')
                ->icon('heroicon-o-cube')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->label('Procurement Items')
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get): void {
                            self::syncOrderTotals($set, $get);
                        })
                        ->schema([
                            Forms\Components\Select::make('health_product_id')
                                ->label('Product')
                                ->options(fn() => HealthProduct::query()
                                    ->where('status', 'active')
                                    ->orderBy('name')
                                    ->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->prefixIcon('heroicon-o-beaker')
                                ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                    $product = HealthProduct::with('inventoryItem')->find($state);

                                    if (!$product) {
                                        return;
                                    }

                                    $set('inventory_item_id', $product->inventory_item_id);
                                    $set('unit_cost', (float) ($product->inventoryItem?->unit_cost ?? 0));

                                    self::syncLineTotals($set, $get);
                                }),
                            Forms\Components\Hidden::make('inventory_item_id'),
                            Forms\Components\TextInput::make('quantity_ordered')
                                ->label('Qty Ordered')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->live(onBlur: true)
                                ->prefixIcon('heroicon-o-scale')
                                ->afterStateUpdated(fn(Set $set, Get $get) => self::syncLineTotals($set, $get)),
                            Forms\Components\TextInput::make('quantity_received')
                                ->label('Qty Received')
                                ->numeric()
                                ->default(0)
                                ->prefixIcon('heroicon-o-archive-box'),
                            Forms\Components\TextInput::make('unit_cost')
                                ->label('Unit Cost')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->live(onBlur: true)
                                ->prefixIcon('heroicon-o-banknotes')
                                ->afterStateUpdated(fn(Set $set, Get $get) => self::syncLineTotals($set, $get)),
                            Forms\Components\TextInput::make('line_subtotal')
                                ->label('Line Subtotal')
                                ->numeric()
                                ->default(0)
                                ->readOnly()
                                ->dehydrated()
                                ->prefixIcon('heroicon-o-calculator'),
                            Forms\Components\TextInput::make('discount_amount')
                                ->label('Item Discount')
                                ->numeric()
                                ->default(0)
                                ->live(onBlur: true)
                                ->prefixIcon('heroicon-o-minus-circle')
                                ->afterStateUpdated(fn(Set $set, Get $get) => self::syncLineTotals($set, $get)),
                            Forms\Components\TextInput::make('tax_rate')
                                ->label('Tax %')
                                ->numeric()
                                ->default(0)
                                ->live(onBlur: true)
                                ->prefixIcon('heroicon-o-receipt-percent')
                                ->afterStateUpdated(fn(Set $set, Get $get) => self::syncLineTotals($set, $get)),
                            Forms\Components\TextInput::make('tax_amount')
                                ->label('Tax Amount')
                                ->numeric()
                                ->default(0)
                                ->readOnly()
                                ->dehydrated()
                                ->prefixIcon('heroicon-o-receipt-percent'),
                            Forms\Components\TextInput::make('line_total')
                                ->label('Line Total')
                                ->numeric()
                                ->default(0)
                                ->readOnly()
                                ->dehydrated()
                                ->prefixIcon('heroicon-o-currency-dollar'),
                            Forms\Components\TextInput::make('batch_number')
                                ->label('Batch Number')
                                ->prefixIcon('heroicon-o-hashtag'),
                            Forms\Components\DatePicker::make('expiry_date')
                                ->label('Expiry Date')
                                ->prefixIcon('heroicon-o-calendar-days'),
                        ])
                        ->columns(4)
                        ->addActionLabel('Add Product')
                        ->reorderable(false)
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Invoice Totals')
                ->description('Totals are calculated live from procurement items, item taxes, item discounts, order discount, payments, and other charges.')
                ->icon('heroicon-o-banknotes')
                ->columns(4)
                ->schema([
                    Forms\Components\TextInput::make('discount_amount')
                        ->label('Order Discount')
                        ->numeric()
                        ->default(0)
                        ->live(onBlur: true)
                        ->prefixIcon('heroicon-o-minus-circle')
                        ->afterStateUpdated(function (Set $set, Get $get): void {
                            self::syncOrderTotals($set, $get);
                        }),
                    Forms\Components\TextInput::make('other_charges')
                        ->label('Other Charges')
                        ->numeric()
                        ->default(0)
                        ->live(onBlur: true)
                        ->prefixIcon('heroicon-o-plus-circle')
                        ->afterStateUpdated(function (Set $set, Get $get): void {
                            self::syncOrderTotals($set, $get);
                        }),
                    Forms\Components\TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->numeric()
                        ->default(0)
                        ->readOnly()
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-calculator'),
                    Forms\Components\TextInput::make('tax_amount')
                        ->label('Item Tax')
                        ->numeric()
                        ->default(0)
                        ->readOnly()
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-receipt-percent'),
                    Forms\Components\TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->numeric()
                        ->default(0)
                        ->readOnly()
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-banknotes'),
                    Forms\Components\TextInput::make('amount_paid')
                        ->label('Amount Paid')
                        ->numeric()
                        ->default(0)
                        ->readOnly()
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-wallet'),
                    Forms\Components\TextInput::make('balance_due')
                        ->label('Balance Due')
                        ->numeric()
                        ->default(0)
                        ->readOnly()
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-exclamation-triangle'),
                    Forms\Components\Placeholder::make('payment_note')
                        ->label('Payment Point')
                        ->content('You may record an initial payment below, or pay later from the Purchase Orders table using Pay Invoice.')
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Initial Supplier Payment')
                ->description('Optional. Record a deposit or full supplier payment while creating this purchase order.')
                ->icon('heroicon-o-banknotes')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('record_initial_payment')
                        ->label('Record payment now?')
                        ->helperText('Turn this on if you are paying a deposit or full amount immediately.')
                        ->live()
                        ->dehydrated(false)
                        ->default(false)
                        ->afterStateUpdated(function (Set $set, Get $get): void {
                            self::syncOrderTotals($set, $get);
                        })
                        ->columnSpanFull(),
                    Forms\Components\DatePicker::make('initial_payment_date')
                        ->label('Payment Date')
                        ->default(now('Africa/Nairobi'))
                        ->visible(fn(Get $get) => (bool) $get('record_initial_payment'))
                        ->dehydrated(false)
                        ->prefixIcon('heroicon-o-calendar-days'),
                    Forms\Components\TextInput::make('initial_payment_amount')
                        ->label('Amount Paid')
                        ->numeric()
                        ->default(0)
                        ->live(onBlur: true)
                        ->visible(fn(Get $get) => (bool) $get('record_initial_payment'))
                        ->dehydrated(false)
                        ->prefixIcon('heroicon-o-banknotes')
                        ->afterStateUpdated(function (Set $set, Get $get): void {
                            self::syncOrderTotals($set, $get);
                        }),
                    Forms\Components\Select::make('initial_payment_method')
                        ->label('Payment Method')
                        ->options([
                            'cash' => 'Cash',
                            'bank' => 'Bank Transfer',
                            'mpesa_b2b' => 'M-Pesa B2B / STK Transfer',
                            'cheque' => 'Cheque',
                        ])
                        ->live()
                        ->visible(fn(Get $get) => (bool) $get('record_initial_payment'))
                        ->dehydrated(false)
                        ->prefixIcon('heroicon-o-credit-card'),
                    Forms\Components\TextInput::make('initial_mpesa_reference')
                        ->label('M-Pesa Reference')
                        ->visible(fn(Get $get) =>
                            (bool) $get('record_initial_payment') &&
                            $get('initial_payment_method') === 'mpesa_b2b')
                        ->dehydrated(false)
                        ->prefixIcon('heroicon-o-device-phone-mobile'),
                    Forms\Components\TextInput::make('initial_bank_name')
                        ->label('Bank Name')
                        ->visible(fn(Get $get) =>
                            (bool) $get('record_initial_payment') &&
                            $get('initial_payment_method') === 'bank')
                        ->dehydrated(false)
                        ->prefixIcon('heroicon-o-building-library'),
                    Forms\Components\TextInput::make('initial_bank_reference')
                        ->label('Bank Reference')
                        ->visible(fn(Get $get) =>
                            (bool) $get('record_initial_payment') &&
                            $get('initial_payment_method') === 'bank')
                        ->dehydrated(false)
                        ->prefixIcon('heroicon-o-hashtag'),
                    Forms\Components\TextInput::make('initial_cheque_number')
                        ->label('Cheque Number')
                        ->visible(fn(Get $get) =>
                            (bool) $get('record_initial_payment') &&
                            $get('initial_payment_method') === 'cheque')
                        ->dehydrated(false)
                        ->prefixIcon('heroicon-o-document-text'),
                    Forms\Components\Textarea::make('initial_payment_notes')
                        ->label('Payment Notes')
                        ->rows(3)
                        ->visible(fn(Get $get) => (bool) $get('record_initial_payment'))
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('purchase_order_number')
                    ->label('PO No.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-clipboard-document-list'),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->icon('heroicon-o-document-text'),
                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->icon('heroicon-o-building-storefront'),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Order Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'received' => 'success',
                        'partially_received' => 'warning',
                        'ordered' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->money('KES'),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('KES'),
                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('KES'),
            ])
            ->actions([
                Tables\Actions\Action::make('printInvoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn(PurchaseOrder $record) => route('procurement.purchase-orders.invoice', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('recordPayment')
                    ->label('Pay Invoice')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalWidth('2xl')
                    ->modalHeading('Record Supplier Invoice Payment')
                    ->modalDescription(fn(PurchaseOrder $record): string =>
                        'You are paying Procurement Invoice '
                        . ($record->invoice_number ?: $record->purchase_order_number)
                        . '. Current balance: KES '
                        . number_format((float) $record->balance_due, 2))
                    ->visible(fn(PurchaseOrder $record): bool =>
                        (float) $record->balance_due > 0 &&
                        (auth()->user()?->can('create purchase order payments') ?? false))
                    ->form([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->default(now('Africa/Nairobi'))
                            ->required()
                            ->prefixIcon('heroicon-o-calendar-days'),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount Paid')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(fn(PurchaseOrder $record) => (float) $record->balance_due)
                            ->maxValue(fn(PurchaseOrder $record) => (float) $record->balance_due)
                            ->helperText(fn(PurchaseOrder $record): string =>
                                'Maximum payable balance: KES ' . number_format((float) $record->balance_due, 2))
                            ->prefixIcon('heroicon-o-banknotes'),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->required()
                            ->live()
                            ->options([
                                'cash' => 'Cash',
                                'bank' => 'Bank Transfer',
                                'mpesa_b2b' => 'M-Pesa B2B / STK Transfer',
                                'cheque' => 'Cheque',
                            ])
                            ->prefixIcon('heroicon-o-credit-card'),
                        Forms\Components\TextInput::make('mpesa_reference')
                            ->label('M-Pesa Reference')
                            ->visible(fn(Get $get): bool => $get('payment_method') === 'mpesa_b2b')
                            ->required(fn(Get $get): bool => $get('payment_method') === 'mpesa_b2b')
                            ->prefixIcon('heroicon-o-device-phone-mobile'),
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->visible(fn(Get $get): bool => $get('payment_method') === 'bank')
                            ->required(fn(Get $get): bool => $get('payment_method') === 'bank')
                            ->prefixIcon('heroicon-o-building-library'),
                        Forms\Components\TextInput::make('bank_reference')
                            ->label('Bank Reference')
                            ->visible(fn(Get $get): bool => $get('payment_method') === 'bank')
                            ->required(fn(Get $get): bool => $get('payment_method') === 'bank')
                            ->prefixIcon('heroicon-o-hashtag'),
                        Forms\Components\TextInput::make('cheque_number')
                            ->label('Cheque Number')
                            ->visible(fn(Get $get): bool => $get('payment_method') === 'cheque')
                            ->required(fn(Get $get): bool => $get('payment_method') === 'cheque')
                            ->prefixIcon('heroicon-o-document-text'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Payment Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (PurchaseOrder $record, array $data): void {
                        PurchaseOrderPayment::create([
                            'purchase_order_id' => $record->id,
                            'payment_date' => $data['payment_date'],
                            'amount' => (float) $data['amount'],
                            'payment_method' => $data['payment_method'],
                            'status' => 'successful',
                            'mpesa_reference' => $data['mpesa_reference'] ?? null,
                            'bank_name' => $data['bank_name'] ?? null,
                            'bank_reference' => $data['bank_reference'] ?? null,
                            'cheque_number' => $data['cheque_number'] ?? null,
                            'notes' => $data['notes'] ?? null,
                        ]);

                        $record->refresh();
                        $record->syncPaymentTotals();

                        Notification::make()
                            ->title('Supplier invoice payment recorded')
                            ->body('The payment has been linked to invoice ' . ($record->invoice_number ?: $record->purchase_order_number) . '.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('latestPaymentVoucher')
                    ->label('Payment Voucher')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->visible(fn($record): bool =>
                        $record->payments()->exists() &&
                        (
                            auth()->user()?->can('view purchase order payments') ||
                            auth()->user()?->can('view procurement dashboard') ||
                            auth()->user()?->hasRole('Admin') ||
                            auth()->user()?->hasRole('Administrator')
                        ))
                    ->url(function ($record): string {
                        $payment = $record
                            ->payments()
                            ->latest('payment_date')
                            ->latest('id')
                            ->first();

                        return route('procurement.payments.voucher', $payment);
                    })
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('receiveStock')
                    ->label('Receive')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn(PurchaseOrder $record): bool =>
                        (auth()->user()?->can('receive purchase orders') ?? false) &&
                        $record->status !== 'received')
                    ->action(function (PurchaseOrder $record): void {
                        $record->receiveStock();

                        Notification::make()
                            ->title('Stock received')
                            ->body('Stock IN movements were created successfully.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->icon('heroicon-o-trash'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
