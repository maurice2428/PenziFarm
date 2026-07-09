<?php

namespace App\Filament\Resources\Sales\SalesInvoiceResource\RelationManagers;

use App\Models\Sales\SalesPayment;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Invoice Payments';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('payment_date')
                ->default(now('Africa/Nairobi'))
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->required()
                ->minValue(1),
            Forms\Components\Select::make('payment_method')
                ->options([
                    'mpesa_stk' => 'M-Pesa STK Push',
                    'mpesa_paybill' => 'M-Pesa Paybill / Offline M-Pesa',
                    'bank_transfer' => 'Bank Transfer',
                    'cash' => 'Cash',
                    'cheque' => 'Cheque',
                    'other' => 'Other',
                ])
                ->required()
                ->live(),
            Forms\Components\TextInput::make('mpesa_receipt_number')
                ->label('M-Pesa Receipt Number')
                ->visible(fn(Forms\Get $get) => in_array($get('payment_method'), ['mpesa_stk', 'mpesa_paybill']))
                ->required(fn(Forms\Get $get) => $get('payment_method') === 'mpesa_paybill'),
            Forms\Components\TextInput::make('reference_number')
                ->label('Reference Number'),
            Forms\Components\TextInput::make('paid_by_name'),
            Forms\Components\TextInput::make('paid_by_phone')
                ->tel(),
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'successful' => 'Successful',
                    'failed' => 'Failed',
                    'cancelled' => 'Cancelled',
                    'reversed' => 'Reversed',
                ])
                ->default('successful')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Payment No.')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('payment_method_label')
                    ->label('Method')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('KES'),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => match ($record->status) {
                        'successful' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('mpesa_receipt_number')
                    ->label('M-Pesa Receipt')
                    ->searchable()
                    ->copyable()
                    ->placeholder('Not confirmed'),
                Tables\Columns\TextColumn::make('paid_by_phone')
                    ->label('Phone')
                    ->searchable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Payment')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['sales_invoice_id'] = $this->ownerRecord->id;
                        $data['customer_id'] = $this->ownerRecord->customer_id;
                        $data['received_by'] = auth()->id();

                        if (
                            in_array($data['payment_method'], ['mpesa_paybill', 'mpesa_stk']) &&
                            filled($data['mpesa_receipt_number'] ?? null)
                        ) {
                            $data['status'] = 'successful';
                            $data['reference_number'] = $data['mpesa_receipt_number'];
                            $data['verified_by'] = auth()->id();
                            $data['verified_at'] = now();
                        }

                        return $data;
                    }),
            ])
            /*  ->actions([
                  Tables\Actions\Action::make('verifyMpesaCode')
                      ->label('Verify M-Pesa Code')
                      ->icon('heroicon-o-shield-check')
                      ->color('success')
                      ->visible(fn(SalesPayment $record) =>
                          in_array($record->payment_method, ['mpesa_stk', 'mpesa_paybill']) &&
                          $record->status !== 'successful')
                      ->form([
                          Forms\Components\TextInput::make('mpesa_receipt_number')
                              ->label('M-Pesa Receipt Number')
                              ->required()
                              ->maxLength(100),
                          Forms\Components\Textarea::make('verification_notes')
                              ->label('Verification Notes')
                              ->placeholder('Example: Verified from customer M-Pesa message / statement.')
                              ->rows(3),
                      ])
                      ->action(function (SalesPayment $record, array $data) {
                          $receipt = strtoupper(trim($data['mpesa_receipt_number']));

                          $duplicatePayment = SalesPayment::query()
                              ->where('mpesa_receipt_number', $receipt)
                              ->whereKeyNot($record->id)
                              ->first();

                          if ($duplicatePayment) {
                              Notification::make()
                                  ->title('Duplicate M-Pesa receipt')
                                  ->body('This receipt number is already used on payment ' . $duplicatePayment->payment_number)
                                  ->danger()
                                  ->send();

                              return;
                          }

                          $record->update([
                              'status' => 'successful',
                              'mpesa_receipt_number' => $receipt,
                              'reference_number' => $receipt,
                              'verified_by' => auth()->id(),
                              'verified_at' => now(),
                              'notes' => trim(($record->notes ?? '') . "\n" . ($data['verification_notes'] ?? 'Manually verified using M-Pesa receipt number.')),
                          ]);

                          $record
                              ->mpesaTransactions()
                              ->latest('id')
                              ->first()
                              ?->update([
                                  'status' => 'successful',
                                  'mpesa_receipt_number' => $receipt,
                                  'result_code' => 'MANUAL',
                                  'result_desc' => 'Manually verified using receipt number.',
                                  'paid_at' => now(),
                              ]);

                          $record->invoice?->syncPaymentTotals();

                          Notification::make()
                              ->title('Payment verified')
                              ->body('M-Pesa receipt saved: ' . $receipt)
                              ->success()
                              ->send();
                      }),
                  Tables\Actions\Action::make('confirmMpesa')
                      ->label('Confirm M-Pesa')
                      ->icon('heroicon-o-check-badge')
                      ->color('success')
                      ->visible(fn(SalesPayment $record) =>
                          in_array($record->payment_method, ['mpesa_stk', 'mpesa_paybill']) &&
                          $record->status !== 'successful')
                      ->form([
                          Forms\Components\TextInput::make('mpesa_receipt_number')
                              ->label('M-Pesa Receipt Number')
                              ->required(),
                          Forms\Components\Textarea::make('notes')
                              ->label('Confirmation Notes'),
                      ])
                      ->action(function (SalesPayment $record, array $data) {
                          $record->update([
                              'status' => 'successful',
                              'mpesa_receipt_number' => $data['mpesa_receipt_number'],
                              'reference_number' => $data['mpesa_receipt_number'],
                              'verified_by' => auth()->id(),
                              'verified_at' => now(),
                              'notes' => trim(($record->notes ?? '') . "\n" . ($data['notes'] ?? 'Manually confirmed via M-Pesa receipt.')),
                          ]);

                          $record->invoice?->syncPaymentTotals();

                          Notification::make()
                              ->title('M-Pesa payment confirmed')
                              ->body('Receipt saved: ' . $data['mpesa_receipt_number'])
                              ->success()
                              ->send();
                      }),
                  Tables\Actions\EditAction::make(),
                  Tables\Actions\ViewAction::make(),
              ]);

            ->actions([
                Tables\Actions\Action::make('verifyMpesaCode')
                    ->label('Verify M-Pesa Code')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn(SalesPayment $record) =>
                        in_array($record->payment_method, ['mpesa_stk', 'mpesa_paybill']) &&
                        $record->status !== 'successful')
                    ->form([
                        Forms\Components\TextInput::make('mpesa_receipt_number')
                            ->label('M-Pesa Receipt Number')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('Verification Notes')
                            ->placeholder('Example: Verified from customer M-Pesa message / statement.')
                            ->rows(3),
                    ])
                    ->action(function (SalesPayment $record, array $data) {
                        $receipt = strtoupper(trim($data['mpesa_receipt_number']));

                        $duplicatePayment = SalesPayment::query()
                            ->where('mpesa_receipt_number', $receipt)
                            ->whereKeyNot($record->id)
                            ->first();

                        if ($duplicatePayment) {
                            Notification::make()
                                ->title('Duplicate M-Pesa receipt')
                                ->body('This receipt number is already used on payment ' . $duplicatePayment->payment_number)
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => 'successful',
                            'mpesa_receipt_number' => $receipt,
                            'reference_number' => $receipt,
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                            'notes' => trim(($record->notes ?? '') . "\n" . ($data['verification_notes'] ?? 'Manually verified using M-Pesa receipt number.')),
                        ]);

                        $record
                            ->mpesaTransactions()
                            ->latest('id')
                            ->first()
                            ?->update([
                                'status' => 'successful',
                                'mpesa_receipt_number' => $receipt,
                                'result_code' => 'MANUAL',
                                'result_desc' => 'Manually verified using receipt number.',
                                'paid_at' => now(),
                            ]);

                        $record->invoice?->syncPaymentTotals();

                        Notification::make()
                            ->title('Payment verified')
                            ->body('M-Pesa receipt saved: ' . $receipt)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ]);*/
            ->actions([
                Tables\Actions\Action::make('verifyMpesaCode')
                    ->label('Verify M-Pesa Code')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn(SalesPayment $record) =>
                        in_array($record->payment_method, ['mpesa_stk', 'mpesa_paybill']) &&
                        $record->status !== 'successful')
                    ->form([
                        Forms\Components\TextInput::make('mpesa_receipt_number')
                            ->label('M-Pesa Receipt Number')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('Verification Notes')
                            ->rows(3),
                    ])
                    ->action(function (SalesPayment $record, array $data) {
                        $receipt = strtoupper(trim($data['mpesa_receipt_number']));

                        $c2b = \App\Models\Sales\MpesaC2BTransaction::query()
                            ->where('trans_id', $receipt)
                            ->first();

                        if (!$c2b) {
                            Notification::make()
                                ->title('Transaction not found')
                                ->body('This receipt number has not been received from the Safaricom C2B confirmation URL.')
                                ->danger()
                                ->send();

                            return;
                        }

                        if ((float) $c2b->trans_amount !== (float) $record->amount) {
                            Notification::make()
                                ->title('Amount mismatch')
                                ->body('C2B amount is KES ' . number_format($c2b->trans_amount, 2) . ', but payment amount is KES ' . number_format($record->amount, 2))
                                ->danger()
                                ->send();

                            return;
                        }

                        if ($c2b->sales_payment_id && $c2b->sales_payment_id !== $record->id) {
                            Notification::make()
                                ->title('Already used')
                                ->body('This M-Pesa receipt has already been attached to another payment.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => 'successful',
                            'mpesa_receipt_number' => $receipt,
                            'reference_number' => $receipt,
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                            'paid_by_phone' => $c2b->phone_number ?: $record->paid_by_phone,
                            'paid_by_name' => trim($c2b->first_name . ' ' . $c2b->middle_name . ' ' . $c2b->last_name) ?: $record->paid_by_name,
                            'notes' => trim(($record->notes ?? '') . "\nVerified against Safaricom C2B confirmation. " . ($data['verification_notes'] ?? '')),
                        ]);

                        $c2b->update([
                            'sales_payment_id' => $record->id,
                            'sales_invoice_id' => $record->sales_invoice_id,
                            'customer_id' => $record->customer_id,
                            'status' => 'verified',
                            'verified_at' => now(),
                        ]);

                        $record->invoice?->syncPaymentTotals();

                        Notification::make()
                            ->title('Payment verified')
                            ->body('Receipt confirmed from C2B: ' . $receipt)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
