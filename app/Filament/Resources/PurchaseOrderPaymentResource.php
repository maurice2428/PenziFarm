<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderPaymentResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Services\Procurement\ProcurementLifecycleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class PurchaseOrderPaymentResource extends Resource
{
    protected static ?string $model =
        PurchaseOrderPayment::class;

    protected static ?string $navigationGroup =
        'Procurement';

    protected static ?string $navigationLabel =
        'Payment(s)';

    protected static ?string $navigationIcon =
        'heroicon-o-banknotes';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'view purchase order payments'
        ) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(
            'create purchase order payments'
        ) ?? false;
    }

    public static function canEdit($record): bool
    {
        return (
            auth()->user()?->can(
                'edit purchase order payments'
            ) ?? false
        )
            && ! $record->is_successful
            && $record->status !== 'reversed';
    }

    public static function canDelete($record): bool
    {
        return (
            auth()->user()?->can(
                'delete purchase order payments'
            ) ?? false
        )
            && app(
                ProcurementLifecycleService::class
            )->canDeletePayment($record);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make(
                    'Supplier Invoice / Purchase Order'
                )
                    ->description(
                        'Select the supplier invoice being paid. '
                        . 'Successful payments become immutable and '
                        . 'must be reversed rather than deleted.'
                    )
                    ->icon('heroicon-o-document-text')
                    ->columns([
                        'default' => 1,
                        'md' => 6,
                        'xl' => 12,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make(
                            'payment_number'
                        )
                            ->label('Payment No.')
                            ->readOnly()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->prefixIcon(
                                'heroicon-o-hashtag'
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 3,
                            ]),

                        Forms\Components\Select::make(
                            'purchase_order_id'
                        )
                            ->label(
                                'Purchase Order / Supplier Invoice'
                            )
                            ->options(
                                function (
                                    ?PurchaseOrderPayment $record
                                ): array {
                                    return PurchaseOrder::query()
                                        ->with('supplier')
                                        ->where(function (
                                            $query
                                        ) use ($record): void {
                                            $query->where(
                                                'balance_due',
                                                '>',
                                                0
                                            );

                                            if (
                                                $record
                                                    ?->purchase_order_id
                                            ) {
                                                $query->orWhere(
                                                    'id',
                                                    $record
                                                        ->purchase_order_id
                                                );
                                            }
                                        })
                                        ->orderByDesc('order_date')
                                        ->orderByDesc('id')
                                        ->get()
                                        ->mapWithKeys(
                                            function (
                                                PurchaseOrder $order
                                            ): array {
                                                $supplier =
                                                    $order->supplier
                                                        ?->company_name
                                                    ?? 'Unknown Supplier';

                                                return [
                                                    $order->id =>
                                                        $order
                                                            ->purchase_order_number
                                                        . ' | '
                                                        . (
                                                            $order
                                                                ->invoice_number
                                                            ?? 'No Invoice'
                                                        )
                                                        . ' | '
                                                        . $supplier
                                                        . ' | Payable: KES '
                                                        . number_format(
                                                            (float) $order
                                                                ->balance_due,
                                                            2
                                                        ),
                                                ];
                                            }
                                        )
                                        ->all();
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->native(false)
                            ->prefixIcon(
                                'heroicon-o-clipboard-document-list'
                            )
                            ->afterStateUpdated(
                                function (
                                    Forms\Set $set,
                                    ?int $state
                                ): void {
                                    $order = PurchaseOrder::query()
                                        ->find($state);

                                    $set(
                                        'amount',
                                        $order
                                            ? number_format(
                                                (float) $order
                                                    ->balance_due,
                                                2,
                                                '.',
                                                ''
                                            )
                                            : null
                                    );
                                }
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 4,
                                'xl' => 6,
                            ]),

                        Forms\Components\Placeholder::make(
                            'invoice_position'
                        )
                            ->label('Invoice Position')
                            ->content(
                                function (
                                    Forms\Get $get
                                ): string {
                                    $order = PurchaseOrder::query()
                                        ->find(
                                            $get(
                                                'purchase_order_id'
                                            )
                                        );

                                    if (! $order) {
                                        return 'Select an invoice to view its position.';
                                    }

                                    return 'Invoice total: KES '
                                        . number_format(
                                            (float) $order
                                                ->grand_total,
                                            2
                                        )
                                        . ' | Paid: KES '
                                        . number_format(
                                            (float) $order
                                                ->amount_paid,
                                            2
                                        )
                                        . ' | Balance: KES '
                                        . number_format(
                                            (float) $order
                                                ->balance_due,
                                            2
                                        );
                                }
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 6,
                                'xl' => 3,
                            ]),
                    ]),

                Forms\Components\Section::make(
                    'Payment Details'
                )
                    ->description(
                        'Capture the exact transaction date, time, '
                        . 'amount, method and verifiable reference.'
                    )
                    ->icon('heroicon-o-banknotes')
                    ->columns([
                        'default' => 1,
                        'md' => 6,
                        'xl' => 12,
                    ])
                    ->schema([
                        Forms\Components\DateTimePicker::make(
                            'paid_at'
                        )
                            ->label('Payment Date & Time')
                            ->default(
                                now('Africa/Nairobi')
                            )
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->prefixIcon(
                                'heroicon-o-calendar-days'
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 3,
                            ]),

                        Forms\Components\TextInput::make(
                            'amount'
                        )
                            ->label('Amount Paid')
                            ->prefix('KES')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText(
                                'Use the full balance or enter a '
                                . 'smaller amount for a partial payment.'
                            )
                            ->suffixAction(
                                Forms\Components\Actions\Action::make(
                                    'use_payable_balance'
                                )
                                    ->label('Use Balance')
                                    ->icon(
                                        'heroicon-o-arrow-path'
                                    )
                                    ->action(
                                        function (
                                            Forms\Get $get,
                                            Forms\Set $set
                                        ): void {
                                            $order =
                                                PurchaseOrder::query()
                                                    ->find(
                                                        $get(
                                                            'purchase_order_id'
                                                        )
                                                    );

                                            if ($order) {
                                                $set(
                                                    'amount',
                                                    number_format(
                                                        (float) $order
                                                            ->balance_due,
                                                        2,
                                                        '.',
                                                        ''
                                                    )
                                                );
                                            }
                                        }
                                    )
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 3,
                            ]),

                        Forms\Components\Select::make(
                            'payment_method'
                        )
                            ->label('Payment Method')
                            ->required()
                            ->live()
                            ->native(false)
                            ->options([
                                'cash' => 'Cash',
                                'bank' => 'Bank Transfer',
                                'mpesa_b2b' =>
                                    'M-Pesa B2B / STK Transfer',
                                'cheque' => 'Cheque',
                            ])
                            ->prefixIcon(
                                'heroicon-o-credit-card'
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 3,
                            ]),

                        Forms\Components\Select::make('status')
                            ->label('Payment Status')
                            ->default('successful')
                            ->required()
                            ->native(false)
                            ->options([
                                'pending' => 'Pending',
                                'successful' => 'Successful',
                                'failed' => 'Failed',
                            ])
                            ->prefixIcon(
                                'heroicon-o-check-badge'
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 3,
                            ]),

                        Forms\Components\Section::make(
                            'M-Pesa / STK Transaction'
                        )
                            ->description(
                                'These values provide a complete '
                                . 'M-Pesa audit trail.'
                            )
                            ->icon(
                                'heroicon-o-device-phone-mobile'
                            )
                            ->visible(
                                fn (
                                    Forms\Get $get
                                ): bool =>
                                    $get('payment_method')
                                    === 'mpesa_b2b'
                            )
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 4,
                            ])
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\TextInput::make(
                                    'mpesa_phone'
                                )
                                    ->label('Phone Number')
                                    ->tel()
                                    ->prefixIcon(
                                        'heroicon-o-phone'
                                    ),

                                Forms\Components\TextInput::make(
                                    'mpesa_receipt_number'
                                )
                                    ->label(
                                        'M-Pesa Receipt / Code'
                                    )
                                    ->required(
                                        fn (
                                            Forms\Get $get
                                        ): bool =>
                                            $get('status')
                                            === 'successful'
                                    )
                                    ->prefixIcon(
                                        'heroicon-o-hashtag'
                                    ),

                                Forms\Components\TextInput::make(
                                    'mpesa_merchant_request_id'
                                )
                                    ->label(
                                        'Merchant Request ID'
                                    ),

                                Forms\Components\TextInput::make(
                                    'mpesa_checkout_request_id'
                                )
                                    ->label(
                                        'Checkout Request ID'
                                    ),

                                Forms\Components\TextInput::make(
                                    'mpesa_result_code'
                                )
                                    ->label('Result Code'),

                                Forms\Components\TextInput::make(
                                    'mpesa_result_description'
                                )
                                    ->label(
                                        'Result Description'
                                    )
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 3,
                                    ]),
                            ]),

                        Forms\Components\TextInput::make(
                            'bank_name'
                        )
                            ->label('Bank Name')
                            ->visible(
                                fn (
                                    Forms\Get $get
                                ): bool =>
                                    $get('payment_method')
                                    === 'bank'
                            )
                            ->required(
                                fn (
                                    Forms\Get $get
                                ): bool =>
                                    $get('payment_method')
                                    === 'bank'
                            )
                            ->prefixIcon(
                                'heroicon-o-building-library'
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 4,
                            ]),

                        Forms\Components\TextInput::make(
                            'bank_reference'
                        )
                            ->label('Bank Reference')
                            ->visible(
                                fn (
                                    Forms\Get $get
                                ): bool =>
                                    $get('payment_method')
                                    === 'bank'
                            )
                            ->required(
                                fn (
                                    Forms\Get $get
                                ): bool =>
                                    $get('payment_method')
                                    === 'bank'
                            )
                            ->prefixIcon(
                                'heroicon-o-hashtag'
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 4,
                            ]),

                        Forms\Components\TextInput::make(
                            'cheque_number'
                        )
                            ->label('Cheque Number')
                            ->visible(
                                fn (
                                    Forms\Get $get
                                ): bool =>
                                    $get('payment_method')
                                    === 'cheque'
                            )
                            ->required(
                                fn (
                                    Forms\Get $get
                                ): bool =>
                                    $get('payment_method')
                                    === 'cheque'
                            )
                            ->prefixIcon(
                                'heroicon-o-document-text'
                            )
                            ->columnSpan([
                                'default' => 1,
                                'md' => 3,
                                'xl' => 4,
                            ]),

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
            ->defaultSort('paid_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make(
                    'payment_number'
                )
                    ->label('Payment No.')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag'),

                Tables\Columns\TextColumn::make(
                    'purchaseOrder.purchase_order_number'
                )
                    ->label('PO No.')
                    ->searchable()
                    ->icon(
                        'heroicon-o-clipboard-document-list'
                    ),

                Tables\Columns\TextColumn::make(
                    'purchaseOrder.supplier.company_name'
                )
                    ->label('Supplier')
                    ->searchable()
                    ->icon(
                        'heroicon-o-building-storefront'
                    ),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder(
                        fn (
                            PurchaseOrderPayment $record
                        ): string =>
                            $record->payment_date
                                ?->format('d M Y')
                            ?? 'N/A'
                    )
                    ->sortable()
                    ->icon(
                        'heroicon-o-calendar-days'
                    ),

                Tables\Columns\TextColumn::make(
                    'payment_method_label'
                )
                    ->label('Method')
                    ->badge(),

                Tables\Columns\TextColumn::make(
                    'payment_reference'
                )
                    ->label('Reference / Code')
                    ->copyable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make(
                    'mpesa_phone'
                )
                    ->label('M-Pesa Phone')
                    ->placeholder('N/A')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                Tables\Columns\TextColumn::make(
                    'mpesa_merchant_request_id'
                )
                    ->label('Merchant Request ID')
                    ->copyable()
                    ->placeholder('N/A')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                Tables\Columns\TextColumn::make(
                    'mpesa_checkout_request_id'
                )
                    ->label('Checkout Request ID')
                    ->copyable()
                    ->placeholder('N/A')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                Tables\Columns\TextColumn::make(
                    'mpesa_result_code'
                )
                    ->label('Result Code')
                    ->placeholder('N/A')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            PurchaseOrderPayment $record
                        ): string =>
                            $record->status_label
                    )
                    ->color(
                        fn (
                            PurchaseOrderPayment $record
                        ): string =>
                            $record->status_color
                    ),

                Tables\Columns\TextColumn::make('amount')
                    ->money('KES')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make(
                    'payment_method'
                )
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'bank' => 'Bank Transfer',
                        'mpesa_b2b' =>
                            'M-Pesa B2B / STK Transfer',
                        'cheque' => 'Cheque',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'successful' => 'Successful',
                        'failed' => 'Failed',
                        'reversed' => 'Reversed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->slideOver()
                    ->modalWidth('7xl'),

                Tables\Actions\Action::make('voucher')
                    ->label('Voucher')
                    ->icon(
                        'heroicon-o-document-arrow-down'
                    )
                    ->color('success')
                    ->url(
                        fn (
                            PurchaseOrderPayment $record
                        ): string =>
                            route(
                                'procurement.payments.voucher',
                                $record
                            )
                    )
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (
                            PurchaseOrderPayment $record
                        ): bool =>
                            static::canEdit($record)
                    ),

                Tables\Actions\Action::make(
                    'reversePayment'
                )
                    ->label('Reverse')
                    ->icon(
                        'heroicon-o-arrow-uturn-left'
                    )
                    ->color('danger')
                    ->visible(
                        fn (
                            PurchaseOrderPayment $record
                        ): bool =>
                            $record->can_be_reversed
                            && (
                                auth()->user()?->can(
                                    'reverse purchase order payments'
                                )
                                || auth()->user()?->can(
                                    'delete purchase order payments'
                                )
                                || auth()->user()?->hasAnyRole([
                                    'Administrator',
                                    'Admin',
                                ])
                            )
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Reverse Supplier Payment'
                    )
                    ->modalDescription(
                        'This keeps the audit record, removes the '
                        . 'payment from the PO paid total, and reverses '
                        . 'any linked accounting journal.'
                    )
                    ->form([
                        Forms\Components\Textarea::make(
                            'reversal_reason'
                        )
                            ->label('Reversal Reason')
                            ->required()
                            ->minLength(8)
                            ->rows(3),
                    ])
                    ->action(
                        function (
                            PurchaseOrderPayment $record,
                            array $data
                        ): void {
                            app(
                                ProcurementLifecycleService::class
                            )->reversePayment(
                                $record,
                                $data['reversal_reason']
                            );

                            Notification::make()
                                ->success()
                                ->title('Payment reversed')
                                ->body(
                                    'The payment remains in the '
                                    . 'audit trail as Reversed.'
                                )
                                ->send();
                        }
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->visible(
                        fn (
                            PurchaseOrderPayment $record
                        ): bool =>
                            static::canDelete($record)
                    )
                    ->requiresConfirmation()
                    ->modalDescription(
                        'Only pending, failed or cancelled payments '
                        . 'without an active accounting journal can be '
                        . 'deleted. Successful and reversed payments remain '
                        . 'in the audit trail.'
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make(
                    'reverseSelected'
                )
                    ->label(
                        'Reverse Successful Payments'
                    )
                    ->icon(
                        'heroicon-o-arrow-uturn-left'
                    )
                    ->color('danger')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can(
                                'reverse purchase order payments'
                            )
                            || auth()->user()?->can(
                                'delete purchase order payments'
                            )
                            || auth()->user()?->hasAnyRole([
                                'Administrator',
                                'Admin',
                            ])
                    )
                    ->form([
                        Forms\Components\Textarea::make(
                            'reversal_reason'
                        )
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
                                    )->reversePayment(
                                        $record,
                                        $data['reversal_reason']
                                    );

                                    $reversed++;
                                } catch (Throwable) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title(
                                    "{$reversed} payment(s) reversed"
                                )
                                ->body(
                                    "{$skipped} were protected; "
                                    . "{$failed} could not be reversed."
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

                Tables\Actions\BulkAction::make(
                    'deleteEligible'
                )
                    ->label(
                        'Delete Eligible Payments'
                    )
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can(
                                'delete purchase order payments'
                            )
                            || auth()->user()?->hasAnyRole([
                                'Administrator',
                                'Admin',
                            ])
                    )
                    ->requiresConfirmation()
                    ->action(
                        function (
                            Collection $records
                        ): void {
                            $deleted = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (
                                    ! app(
                                        ProcurementLifecycleService::class
                                    )->canDeletePayment($record)
                                ) {
                                    $skipped++;
                                    continue;
                                }

                                $record->delete();
                                $deleted++;
                            }

                            Notification::make()
                                ->success()
                                ->title(
                                    "{$deleted} payment(s) deleted"
                                )
                                ->body(
                                    "{$skipped} protected payment(s) "
                                    . 'were retained.'
                                )
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListPurchaseOrderPayments::route('/'),
            'create' =>
                Pages\CreatePurchaseOrderPayment::route(
                    '/create'
                ),
            'edit' =>
                Pages\EditPurchaseOrderPayment::route(
                    '/{record}/edit'
                ),
        ];
    }
}
