<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\SalesPaymentResource\Pages;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesPaymentResource extends Resource
{
    protected static ?string $model = SalesPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $modelLabel = 'Sales Payment';

    protected static ?string $pluralModelLabel = 'Sales Payments';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view sales payments') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create sales payments') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit sales payments') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete sales payments') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete sales payments') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore sales payments') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore sales payments') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete sales payments') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete sales payments') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Payment')
                    ->description('Record full or partial payments against a sales invoice.')
                    ->icon('heroicon-o-wallet')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('payment_number')
                            ->label('Payment Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto generated')
                            ->prefixIcon('heroicon-o-hashtag'),
                        Forms\Components\Select::make('sales_invoice_id')
                            ->label('Sales Invoice')
                            ->options(fn() => SalesInvoice::query()
                                ->with('customer')
                                ->whereNotIn('payment_status', ['paid', 'overpaid'])
                                ->orderByDesc('invoice_date')
                                ->get()
                                ->mapWithKeys(fn($invoice) => [
                                    $invoice->id => $invoice->invoice_number
                                        . ' | '
                                        . ($invoice->customer?->name ?? 'No Customer')
                                        . ' | Balance: KES '
                                        . number_format((float) $invoice->balance_due, 2),
                                ]))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            /* ->afterStateUpdated(function ($state, Set $set) {
                                 $invoice = SalesInvoice::with('customer')->find($state);

                                 if (! $invoice) {
                                     return;
                                 }

                                 $set('customer_id', $invoice->customer_id);
                                 $set('amount', $invoice->balance_due);
                                 $set('paid_by_name', $invoice->customer?->name);
                                 $set('paid_by_phone', $invoice->customer?->phone);
                             })*/
                            ->afterStateUpdated(function ($state, Set $set) {
                                $invoice = SalesInvoice::with('customer')->find($state);

                                if (!$invoice) {
                                    return;
                                }

                                $set('customer_id', $invoice->customer_id);
                                $set('amount', $invoice->balance_due);
                                $set('paid_by_name', $invoice->customer?->name);
                                $set('paid_by_phone', $invoice->customer?->phone);
                            })
                            ->prefixIcon('heroicon-o-document-currency-dollar'),
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated()
                            ->prefixIcon('heroicon-o-user-circle'),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->default(now('Africa/Nairobi'))
                            ->required()
                            ->prefixIcon('heroicon-o-calendar-days'),
                        /* Forms\Components\TextInput::make('amount')
                             ->label('Amount Paid')
                             ->numeric()
                             ->required()
                             ->minValue(1)
                             ->prefixIcon('heroicon-o-banknotes')
                             ->helperText('Can be partial, full, or overpayment.'),*/
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount Paid')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(function (Get $get) {
                                $invoice = SalesInvoice::find($get('sales_invoice_id'));

                                return $invoice ? (float) $invoice->balance_due : null;
                            })
                            ->rule(function (Get $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $invoice = SalesInvoice::find($get('sales_invoice_id'));

                                    if (!$invoice) {
                                        $fail('Please select a valid invoice first.');
                                        return;
                                    }

                                    $amount = (float) $value;
                                    $balance = (float) $invoice->balance_due;

                                    if ($amount > $balance) {
                                        $fail('Payment cannot exceed the invoice balance of KES ' . number_format($balance, 2) . '.');
                                    }
                                };
                            })
                            ->helperText(function (Get $get) {
                                $invoice = SalesInvoice::find($get('sales_invoice_id'));

                                if (!$invoice) {
                                    return 'Select an invoice first.';
                                }

                                return 'Remaining balance: KES ' . number_format((float) $invoice->balance_due, 2);
                            })
                            ->prefixIcon('heroicon-o-banknotes'),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'mpesa_stk' => 'M-Pesa STK Push',
                                'mpesa_paybill' => 'M-Pesa Paybill',
                                'bank_transfer' => 'Bank Transfer',
                                'cash' => 'Cash',
                                'cheque' => 'Cheque',
                                'other' => 'Other',
                            ])
                            ->default('cash')
                            ->required()
                            ->live()
                            ->prefixIcon('heroicon-o-credit-card'),
                        Forms\Components\Select::make('status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'successful' => 'Successful',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                                'reversed' => 'Reversed',
                            ])
                            ->default('pending')
                            ->required()
                            ->prefixIcon('heroicon-o-check-badge'),
                    ]),
                Forms\Components\Section::make('Payment Reference Details')
                    ->description('Capture transaction reference, M-Pesa code, bank details, and payer information.')
                    ->icon('heroicon-o-receipt-percent')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->placeholder('Bank reference, cheque number, cash note, etc.')
                            ->prefixIcon('heroicon-o-hashtag'),
                        Forms\Components\TextInput::make('mpesa_receipt_number')
                            ->label('M-Pesa Receipt Number')
                            ->visible(fn(Get $get) => in_array($get('payment_method'), ['mpesa_stk', 'mpesa_paybill']))
                            ->prefixIcon('heroicon-o-device-phone-mobile'),
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->visible(fn(Get $get) => $get('payment_method') === 'bank_transfer')
                            ->prefixIcon('heroicon-o-building-library'),
                        Forms\Components\TextInput::make('paid_by_name')
                            ->label('Paid By Name')
                            ->prefixIcon('heroicon-o-user'),
                        /* Forms\Components\TextInput::make('paid_by_phone')
                             ->label('Paid By Phone')
                             ->tel()
                             ->prefixIcon('heroicon-o-phone'),*/
                        Forms\Components\TextInput::make('paid_by_phone')
                            ->label('Paid By Phone')
                            ->tel()
                            ->required(fn(Get $get) => $get('payment_method') === 'mpesa_stk')
                            ->helperText('Required for STK Push. Use format 2547XXXXXXXX or 07XXXXXXXX.')
                            ->prefixIcon('heroicon-o-phone'),
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
                    ->label('Payment No.')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-document-currency-dollar'),
                Tables\Columns\TextColumn::make('mpesa_receipt_number')
                    ->label('M-Pesa Code')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-device-phone-mobile'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-user-circle'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                Tables\Columns\TextColumn::make('payment_method_label')
                    ->label('Method')
                    ->badge()
                    ->icon('heroicon-o-credit-card'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->icon('heroicon-o-banknotes'),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->icon('heroicon-o-check-badge'),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mpesa_receipt_number')
                    ->label('M-Pesa Code')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('receivedBy.name')
                    ->label('Received By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('d M Y, h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'mpesa_stk' => 'M-Pesa STK Push',
                        'mpesa_paybill' => 'M-Pesa Paybill',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'cheque' => 'Cheque',
                        'other' => 'Other',
                    ]),
                SelectFilter::make('status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'successful' => 'Successful',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'reversed' => 'Reversed',
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('printReceipt')
                    ->label('Receipt PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->visible(fn(SalesPayment $record) =>
                        !$record->trashed() &&
                        $record->status === 'successful' &&
                        (auth()->user()?->can('view sales payments') ?? false))
                    ->action(function (SalesPayment $record) {
                        $record->load([
                            'invoice.customer',
                            'invoice.items.animal',
                            'customer',
                            'receivedBy',
                            'verifiedBy',
                        ]);

                        $pdf = Pdf::loadView('pdf.sales-payment-receipt', [
                            'payment' => $record,
                            'invoice' => $record->invoice,
                            'customer' => $record->customer ?? $record->invoice?->customer,
                            'generatedBy' => auth()->user(),
                            'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
                        ])->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn() => print ($pdf->output()),
                            'receipt-' . $record->payment_number . '.pdf'
                        );
                    }),
                Tables\Actions\ViewAction::make()
                    ->visible(fn() => auth()->user()?->can('view sales payments') ?? false),
                Tables\Actions\Action::make('sendStkPush')
                    ->label('Send STK')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(SalesPayment $record) =>
                        !$record->trashed() &&
                        $record->payment_method === 'mpesa_stk' &&
                        in_array($record->status, ['pending', 'failed']) &&
                        (auth()->user()?->can('create sales payments') ?? false))
                    ->action(function (SalesPayment $record) {
                        try {
                            app(\App\Services\Mpesa\MpesaDarajaService::class)->sendStkPush($record);

                            \Filament\Notifications\Notification::make()
                                ->title('STK Push sent')
                                ->body('The customer has been prompted to enter their M-Pesa PIN.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('STK Push failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) =>
                        !$record->trashed() &&
                        (auth()->user()?->can('edit sales payments') ?? false)),
                /* Tables\Actions\DeleteAction::make()
                     ->visible(fn($record) =>
                         !$record->trashed() &&
                         (auth()->user()?->can('delete sales payments') ?? false)),*/
                Tables\Actions\DeleteAction::make()
                    ->label('Delete Payment')
                    ->modalHeading('Delete Sales Payment')
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('danger')
                    ->modalDescription(fn(SalesPayment $record) =>
                        'You are about to delete payment '
                        . ($record->payment_number ?? '#' . $record->id)
                        . ' amounting to KES '
                        . number_format((float) $record->amount, 2)
                        . '. This will update the linked invoice balance.')
                    ->requiresConfirmation()
                    ->after(function (SalesPayment $record) {
                        // $record->invoice?->refreshPaymentTotals();
                        $record->invoice?->syncPaymentTotals();
                        $record->invoice?->syncAnimalSaleStatus();
                    })
                    ->visible(fn($record) =>
                        !$record->trashed() &&
                        (auth()->user()?->can('delete sales payments') ?? false)),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn($record) =>
                        $record->trashed() &&
                        (auth()->user()?->can('restore sales payments') ?? false)),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn($record) =>
                        $record->trashed() &&
                        (auth()->user()?->can('force delete sales payments') ?? false)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('delete sales payments') ?? false),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('restore sales payments') ?? false),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('force delete sales payments') ?? false),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesPayments::route('/'),
            'create' => Pages\CreateSalesPayment::route('/create'),
            'view' => Pages\ViewSalesPayment::route('/{record}'),
            'edit' => Pages\EditSalesPayment::route('/{record}/edit'),
        ];
    }
}
