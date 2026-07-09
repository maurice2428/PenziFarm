<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Sales\SalesInvoiceResource;
use App\Filament\Resources\Sales\SalesPaymentResource;
use App\Models\Sales\SalesPayment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class SalesRecentPaymentsTable extends TableWidget
{
    public ?array $filters = [];

    protected static ?string $heading = 'Sales Payments Register';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('payment_date', 'desc')
            ->recordUrl(fn (SalesPayment $record) => SalesPaymentResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Payment No.')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->url(fn (SalesPayment $record) => SalesPaymentResource::getUrl('view', ['record' => $record])),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->url(fn (SalesPayment $record) => $record->invoice
                        ? SalesInvoiceResource::getUrl('view', ['record' => $record->invoice])
                        : null
                    ),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->placeholder(fn (SalesPayment $record) => $record->paid_by_name ?: '-'),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method_label')
                    ->label('Method')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mpesa_receipt_number')
                    ->label('M-Pesa Code')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-')
                    ->url(fn (SalesPayment $record) => SalesPaymentResource::getUrl('view', ['record' => $record])),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn (SalesPayment $record) => match ($record->status) {
                        'successful' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        'reversed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'successful' => 'Successful',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'reversed' => 'Reversed',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'mpesa_stk' => 'M-Pesa STK',
                        'mpesa_paybill' => 'M-Pesa Paybill',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'cheque' => 'Cheque',
                        'other' => 'Other',
                    ]),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    protected function getTableQuery(): Builder
    {
        $dateFrom = $this->filters['date_from'] ?? now('Africa/Nairobi')->startOfMonth()->toDateString();
        $dateTo = $this->filters['date_to'] ?? now('Africa/Nairobi')->toDateString();

        return SalesPayment::query()
            ->with(['invoice', 'customer'])
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->when($this->filters['payment_method'] ?? null, fn ($q, $method) => $q->where('payment_method', $method))
            ->when($this->filters['payment_status'] ?? null, function ($q, $status) {
                $q->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('payment_status', $status));
            })
            ->when($this->filters['invoice_status'] ?? null, function ($q, $status) {
                $q->whereHas('invoice', fn ($invoiceQuery) => $invoiceQuery->where('status', $status));
            });
    }
}
