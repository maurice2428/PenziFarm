<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Concerns\ChecksExplicitPermissions;
use App\Filament\Resources\Accounting\OperatingExpensePaymentResource\Pages;
use App\Models\Finance\OperatingExpense;
use App\Models\Finance\OperatingExpensePayment;
use App\Services\Finance\OperatingExpenseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class OperatingExpensePaymentResource extends Resource
{
    use ChecksExplicitPermissions;

    protected static ?string $model = OperatingExpensePayment::class;
    protected static ?string $navigationGroup = 'Accounting';
    protected static ?string $navigationLabel = 'Expense Payments';
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?int $navigationSort = 32;

    public static function canViewAny(): bool
    {
        return static::permits(
            'view operating expense payments'
        );
    }

    public static function canCreate(): bool
    {
        return static::permits(
            'create operating expense payments'
        );
    }

    public static function canEdit($record): bool
    {
        return static::permits(
            'edit operating expense payments'
        ) && $record->status === 'draft';
    }

    public static function canDelete($record): bool
    {
        return static::permits(
            'delete draft operating expense payments'
        ) && $record->status === 'draft';
    }

    public static function canRestore($record): bool
    {
        return static::permits(
            'delete draft operating expense payments'
        );
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Operating Expense Payment')
                    ->description('Create a reviewable payment draft. Posting clears Accounts Payable and credits the selected cash, bank or mobile-money account.')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('payment_number')
                            ->disabled()->dehydrated(false)->placeholder('Auto-generated')
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\Select::make('operating_expense_id')
                            ->label('Expense')
                            ->options(fn (): array => OperatingExpense::query()
                                ->whereIn('status', ['approved', 'partially_paid'])
                                ->where('balance_due', '>', 0)
                                ->orderByDesc('expense_date')
                                ->get()
                                ->mapWithKeys(fn (OperatingExpense $expense): array => [
                                    $expense->id => $expense->expense_number
                                        . ' · ' . $expense->description
                                        . ' · Balance KES ' . number_format((float) $expense->balance_due, 2),
                                ])->all())
                            ->searchable()->preload()->required()->native(false)->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                $expense = OperatingExpense::query()->find($state);
                                if ($expense) $set('amount', (float) $expense->balance_due);
                            })
                            ->disabled(fn (?OperatingExpensePayment $record): bool => filled($record))
                            ->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 5]),
                        Forms\Components\DateTimePicker::make('payment_date')
                            ->default(now('Africa/Nairobi'))->seconds(false)->native(false)->required()
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()->prefix('KES')->minValue(0.01)->required()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'bank' => 'Bank', 'mpesa' => 'M-Pesa', 'airtel_money' => 'Airtel Money',
                                'cash' => 'Cash', 'cheque' => 'Cheque',
                            ])->default('bank')->native(false)->live()->required()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction Reference')->required()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\Select::make('status')
                            ->options(['draft' => 'Draft', 'posted' => 'Posted', 'reversed' => 'Reversed'])
                            ->default('draft')->disabled()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\TextInput::make('mpesa_phone')
                            ->label('Mobile Number')->tel()
                            ->visible(fn (Forms\Get $get): bool => in_array($get('payment_method'), ['mpesa', 'airtel_money'], true))
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Forms\Components\TextInput::make('bank_name')
                            ->visible(fn (Forms\Get $get): bool => $get('payment_method') === 'bank')
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Forms\Components\Textarea::make('notes')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('payment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('expense.expense_number')->label('Expense')->searchable(),
                Tables\Columns\TextColumn::make('expense.description')->label('Description')->limit(35),
                Tables\Columns\TextColumn::make('payment_date')->dateTime('d M Y, H:i')->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('KES')->sortable(),
                Tables\Columns\TextColumn::make('payment_method')->badge()
                    ->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->title()),
                Tables\Columns\TextColumn::make('transaction_reference')->label('Reference')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'posted' => 'success', 'reversed' => 'danger', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'posted' => 'Posted', 'reversed' => 'Reversed',
                ]),
                Tables\Filters\SelectFilter::make('payment_method')->options([
                    'bank' => 'Bank', 'mpesa' => 'M-Pesa', 'airtel_money' => 'Airtel Money', 'cash' => 'Cash', 'cheque' => 'Cheque',
                ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (OperatingExpensePayment $record): bool =>
                            static::permits(
                                'edit operating expense payments'
                            )
                            && $record->status === 'draft'
                    ),
                Tables\Actions\Action::make('post')
                    ->label('Post')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->visible(
                        fn (OperatingExpensePayment $record): bool =>
                            static::permits(
                                'post operating expense payments'
                            )
                            && $record->status === 'draft'
                    )
                    ->action(function (OperatingExpensePayment $record): void {
                        app(OperatingExpenseService::class)->postDraftPayment($record);
                        Notification::make()->success()->title('Expense payment posted')->send();
                    }),
                Tables\Actions\Action::make('reverse')
                    ->label('Reverse')->icon('heroicon-o-arrow-uturn-left')->color('danger')
                    ->visible(
                        fn (OperatingExpensePayment $record): bool =>
                            static::permits(
                                'reverse operating expense payments'
                            )
                            && $record->status === 'posted'
                    )
                    ->form([Forms\Components\Textarea::make('reason')->required()->minLength(5)])
                    ->action(function (OperatingExpensePayment $record, array $data): void {
                        app(OperatingExpenseService::class)->reversePayment($record, $data['reason']);
                        Notification::make()->warning()->title('Expense payment reversed')->send();
                    }),
                Tables\Actions\Action::make('voucher')
                    ->label('Voucher')->icon('heroicon-o-printer')->color('gray')
                    ->visible(fn (OperatingExpensePayment $record): bool => $record->status !== 'draft')
                    ->action(function (OperatingExpensePayment $record) {
                        $record->load(['expense.category', 'expense.supplier', 'poster']);
                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.accounting.operating-expense-payment-voucher', [
                            'payment' => $record, 'generatedBy' => auth()->user(),
                        ])->setPaper('a4');
                        return response()->streamDownload(fn () => print($pdf->output()), $record->payment_number . '.pdf');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (OperatingExpensePayment $record): bool =>
                            static::permits(
                                'delete draft operating expense payments'
                            )
                            && $record->status === 'draft'
                    ),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('postSelected')
                    ->label('Post Selected Drafts')
                    ->visible(
                        fn (): bool => static::permits(
                            'post operating expense payments'
                        )
                    )->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $posted = 0;
                        foreach ($records as $record) {
                            if ($record->status !== 'draft') continue;
                            app(OperatingExpenseService::class)->postDraftPayment($record); $posted++;
                        }
                        Notification::make()->success()->title("{$posted} payment(s) posted")->send();
                    })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('deleteDrafts')
                    ->label('Delete Selected Drafts')
                    ->visible(
                        fn (): bool => static::permits(
                            'delete draft operating expense payments'
                        )
                    )->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->where('status', 'draft')->each->delete())
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperatingExpensePayments::route('/'),
            'create' => Pages\CreateOperatingExpensePayment::route('/create'),
            'edit' => Pages\EditOperatingExpensePayment::route('/{record}/edit'),
        ];
    }
}
