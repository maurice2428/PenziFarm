<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderPaymentResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class PurchaseOrderPaymentResource extends Resource
{
    protected static ?string $model = PurchaseOrderPayment::class;

    protected static ?string $navigationGroup = 'Procurement';

    protected static ?string $navigationLabel = 'Supplier Payments';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view purchase order payments') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create purchase order payments') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit purchase order payments') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete purchase order payments') ?? false;
    }

    /*
     * public static function form(Form $form): Form
     * {
     *     return $form->schema([
     *         Forms\Components\Section::make('Supplier Payment')
     *             ->icon('heroicon-o-banknotes')
     *             ->columns(3)
     *             ->schema([
     *                 Forms\Components\TextInput::make('payment_number')
     *                     ->readOnly()
     *                     ->prefixIcon('heroicon-o-hashtag'),
     *                 Forms\Components\Select::make('purchase_order_id')
     *                     ->label('Purchase Order')
     *                     ->options(fn() => PurchaseOrder::query()
     *                         ->where('balance_due', '>', 0)
     *                         ->orderByDesc('order_date')
     *                         ->pluck('purchase_order_number', 'id'))
     *                     ->searchable()
     *                     ->preload()
     *                     ->required()
     *                     ->prefixIcon('heroicon-o-clipboard-document-list'),
     *                 Forms\Components\DatePicker::make('payment_date')
     *                     ->default(now('Africa/Nairobi'))
     *                     ->required()
     *                     ->prefixIcon('heroicon-o-calendar-days'),
     *                 Forms\Components\TextInput::make('amount')
     *                     ->numeric()
     *                     ->required()
     *                     ->prefixIcon('heroicon-o-banknotes'),
     *                 Forms\Components\Select::make('payment_method')
     *                     ->required()
     *                     ->live()
     *                     ->options([
     *                         'cash' => 'Cash',
     *                         'bank' => 'Bank Transfer',
     *                         'mpesa_b2b' => 'M-Pesa B2B / STK Transfer',
     *                         'cheque' => 'Cheque',
     *                     ])
     *                     ->prefixIcon('heroicon-o-credit-card'),
     *                 Forms\Components\Select::make('status')
     *                     ->default('successful')
     *                     ->options([
     *                         'pending' => 'Pending',
     *                         'successful' => 'Successful',
     *                         'failed' => 'Failed',
     *                         'reversed' => 'Reversed',
     *                     ])
     *                     ->prefixIcon('heroicon-o-check-badge'),
     *                 Forms\Components\TextInput::make('mpesa_reference')
     *                     ->visible(fn(Forms\Get $get) => $get('payment_method') === 'mpesa_b2b')
     *                     ->prefixIcon('heroicon-o-device-phone-mobile'),
     *                 Forms\Components\TextInput::make('bank_name')
     *                     ->visible(fn(Forms\Get $get) => $get('payment_method') === 'bank')
     *                     ->prefixIcon('heroicon-o-building-library'),
     *                 Forms\Components\TextInput::make('bank_reference')
     *                     ->visible(fn(Forms\Get $get) => $get('payment_method') === 'bank')
     *                     ->prefixIcon('heroicon-o-hashtag'),
     *                 Forms\Components\TextInput::make('cheque_number')
     *                     ->visible(fn(Forms\Get $get) => $get('payment_method') === 'cheque')
     *                     ->prefixIcon('heroicon-o-document-text'),
     *                 Forms\Components\Textarea::make('notes')
     *                     ->columnSpanFull(),
     *             ]),
     *     ]);
     * }
     */

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Supplier Invoice / Purchase Order')
                    ->description('Select the supplier invoice being paid. The payable balance will auto-fill into Amount Paid, but you can edit it for partial payments.')
                    ->icon('heroicon-o-document-text')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('payment_number')
                            ->label('Payment No.')
                            ->readOnly()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(3),
                        Forms\Components\Select::make('purchase_order_id')
                            ->label('Purchase Order / Supplier Invoice')
                            ->options(function (?PurchaseOrderPayment $record): array {
                                return PurchaseOrder::query()
                                    ->with('supplier')
                                    ->where(function ($query) use ($record) {
                                        $query->where('balance_due', '>', 0);

                                        if ($record?->purchase_order_id) {
                                            $query->orWhere('id', $record->purchase_order_id);
                                        }
                                    })
                                    ->orderByDesc('order_date')
                                    ->orderByDesc('id')
                                    ->get()
                                    ->mapWithKeys(function (PurchaseOrder $order): array {
                                        $supplier = $order->supplier?->company_name
                                            ?? $order->supplier?->name
                                            ?? 'Unknown Supplier';

                                        $label =
                                            ($order->purchase_order_number ?? 'PO-' . $order->id)
                                            . ' | '
                                            . ($order->invoice_number ?? 'No Invoice')
                                            . ' | '
                                            . $supplier
                                            . ' | Payable: KES '
                                            . number_format((float) ($order->balance_due ?? 0), 2);

                                        return [$order->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->prefixIcon('heroicon-o-clipboard-document-list')
                            ->afterStateUpdated(function (Forms\Set $set, ?int $state): void {
                                if (!$state) {
                                    $set('amount', null);

                                    return;
                                }

                                $order = PurchaseOrder::query()->find($state);

                                if (!$order) {
                                    $set('amount', null);

                                    return;
                                }

                                $set('amount', number_format((float) ($order->balance_due ?? 0), 2, '.', ''));
                            })
                            ->columnSpan(6),
                        Forms\Components\Placeholder::make('invoice_balance_preview')
                            ->label('Invoice Payment Position')
                            ->content(function (Forms\Get $get): string {
                                $order = PurchaseOrder::query()->find($get('purchase_order_id'));

                                if (!$order) {
                                    return 'Select a purchase order to preview payable balance.';
                                }

                                return 'Grand Total: KES '
                                    . number_format((float) ($order->grand_total ?? 0), 2)
                                    . ' | Paid: KES '
                                    . number_format((float) ($order->amount_paid ?? 0), 2)
                                    . ' | Payable Balance: KES '
                                    . number_format((float) ($order->balance_due ?? 0), 2);
                            })
                            ->columnSpan(3),
                    ]),
                Forms\Components\Section::make('Payment Details')
                    ->description('The amount is auto-filled from the payable balance, but can be edited for partial payments.')
                    ->icon('heroicon-o-banknotes')
                    ->columns(12)
                    ->schema([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount Paid')
                            ->prefix('KES')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Auto-filled from the purchase order payable balance. You may edit it for partial payments.')
                            ->prefixIcon('heroicon-o-banknotes')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('use_payable_balance')
                                    ->label('Use Balance')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Fetch current payable balance from selected purchase order')
                                    ->action(function (Forms\Get $get, Forms\Set $set): void {
                                        $order = PurchaseOrder::query()->find($get('purchase_order_id'));

                                        if (!$order) {
                                            return;
                                        }

                                        $set('amount', number_format((float) ($order->balance_due ?? 0), 2, '.', ''));
                                    })
                            )
                            ->columnSpan(3),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->required()
                            ->live()
                            ->native(false)
                            ->options([
                                'cash' => 'Cash',
                                'bank' => 'Bank Transfer',
                                'mpesa_b2b' => 'M-Pesa B2B / STK Transfer',
                                'cheque' => 'Cheque',
                            ])
                            ->prefixIcon('heroicon-o-credit-card')
                            ->columnSpan(3),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->default('successful')
                            ->required()
                            ->native(false)
                            ->options([
                                'pending' => 'Pending',
                                'successful' => 'Successful',
                                'failed' => 'Failed',
                                'reversed' => 'Reversed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->prefixIcon('heroicon-o-check-badge')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('mpesa_reference')
                            ->label('M-Pesa Reference')
                            ->visible(fn(Forms\Get $get): bool => $get('payment_method') === 'mpesa_b2b')
                            ->required(fn(Forms\Get $get): bool => $get('payment_method') === 'mpesa_b2b')
                            ->prefixIcon('heroicon-o-device-phone-mobile')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->visible(fn(Forms\Get $get): bool => $get('payment_method') === 'bank')
                            ->required(fn(Forms\Get $get): bool => $get('payment_method') === 'bank')
                            ->prefixIcon('heroicon-o-building-library')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('bank_reference')
                            ->label('Bank Reference')
                            ->visible(fn(Forms\Get $get): bool => $get('payment_method') === 'bank')
                            ->required(fn(Forms\Get $get): bool => $get('payment_method') === 'bank')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('cheque_number')
                            ->label('Cheque Number')
                            ->visible(fn(Forms\Get $get): bool => $get('payment_method') === 'cheque')
                            ->required(fn(Forms\Get $get): bool => $get('payment_method') === 'cheque')
                            ->prefixIcon('heroicon-o-document-text')
                            ->columnSpan(4),
                        Forms\Components\Textarea::make('notes')
                            ->label('Payment Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('payment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\TextColumn::make('purchaseOrder.purchase_order_number')
                    ->label('PO No.')
                    ->searchable()
                    ->icon('heroicon-o-clipboard-document-list'),
                Tables\Columns\TextColumn::make('purchaseOrder.supplier.company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->icon('heroicon-o-building-storefront'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                Tables\Columns\TextColumn::make('payment_method_label')
                    ->label('Method')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'successful' => 'success',
                        'pending' => 'warning',
                        'failed', 'reversed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->money('KES'),
            ])
            ->actions([
                Tables\Actions\Action::make('voucher')
                    ->label('Voucher')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn(PurchaseOrderPayment $record): string => route('procurement.payments.voucher', $record))
                    ->openUrlInNewTab()
                    ->visible(fn(): bool =>
                        auth()->user()?->can('view purchase order payments') ||
                        auth()->user()?->can('view procurement dashboard') ||
                        auth()->user()?->hasRole('Admin') ||
                        auth()->user()?->hasRole('Administrator')),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Deleting this payment will update the purchase order balance.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrderPayments::route('/'),
            'create' => Pages\CreatePurchaseOrderPayment::route('/create'),
            'edit' => Pages\EditPurchaseOrderPayment::route('/{record}/edit'),
        ];
    }
}
