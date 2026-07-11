<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Concerns\ChecksExplicitPermissions;
use App\Filament\Resources\Accounting\OperatingExpenseResource\Pages;
use App\Models\Accounting\AccountingCostCenter;
use App\Models\Accounting\AccountingProjectFund;
use App\Models\Accounting\AccountingTaxSetting;
use App\Models\Finance\ExpenseCategory;
use App\Models\Finance\OperatingExpense;
use App\Models\Supplier;
use App\Services\Finance\OperatingExpenseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class OperatingExpenseResource extends Resource
{
    use ChecksExplicitPermissions;

    protected static ?string $model = OperatingExpense::class;
    protected static ?string $navigationGroup = 'Accounting';
    protected static ?string $navigationLabel = 'Operating Expenses';
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?int $navigationSort = 30;

    public static function canViewAny(): bool
    {
        return static::permits('view operating expenses');
    }

    public static function canCreate(): bool
    {
        return static::permits('create operating expenses');
    }

    public static function canEdit($record): bool
    {
        return static::permits('edit operating expenses')
            && $record->isDraft();
    }

    public static function canDelete($record): bool
    {
        return static::permits(
            'delete draft operating expenses'
        ) && $record->isDraft();
    }

    public static function canRestore($record): bool
    {
        return static::permits(
            'delete draft operating expenses'
        );
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    protected static function whtRate(
        ?string $code,
        ?string $residency
    ): float {
        if (blank($code)) {
            return 0.0;
        }

        $setting = AccountingTaxSetting::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->orderByDesc('effective_from')
            ->first();

        if ($setting) {
            return $setting->rateFor(
                $residency === 'non_resident'
                    ? 'non_resident'
                    : 'resident'
            );
        }

        return match ($code) {
            'WHT_PROFESSIONAL' =>
                $residency === 'non_resident' ? 20.0 : 5.0,
            'WHT_RENT' =>
                $residency === 'non_resident' ? 30.0 : 10.0,
            'WHT_CONTRACTUAL' =>
                $residency === 'non_resident' ? 20.0 : 3.0,
            default => 0.0,
        };
    }

    protected static function syncTotals(Forms\Set $set, Forms\Get $get): void
    {
        $net = max(0, (float) ($get('net_amount') ?? 0));
        $taxTreatment = $get('tax_treatment');
        $vatRate = $taxTreatment === 'standard_vat'
            ? max(0, (float) ($get('vat_rate') ?? 16))
            : 0;
        $whtRate = max(0, (float) ($get('withholding_tax_rate') ?? 0));

        $vat = round($net * ($vatRate / 100), 2);
        $wht = round($net * ($whtRate / 100), 2);
        $gross = round($net + $vat, 2);
        $payable = round(max(0, $gross - $wht), 2);
        $paid = (float) ($get('paid_amount') ?? 0);

        $set('vat_amount', $vat);
        $set('withholding_tax_amount', $wht);
        $set('gross_amount', $gross);
        $set('payable_amount', $payable);
        $set('balance_due', max(0, $payable - $paid));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Expense Details')
                    ->description('Record rent, fuel, utilities, repairs, professional fees and other operating costs with their accounting allocation.')
                    ->icon('heroicon-o-receipt-percent')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('expense_number')
                            ->label('Expense No.')
                            ->disabled()->dehydrated(false)->placeholder('Auto-generated')
                            ->columnSpan(['default' => 12, 'md' => 3]),

                        Forms\Components\Select::make('expense_category_id')
                            ->label('Expense Category')
                            ->options(fn (): array => ExpenseCategory::query()
                                ->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->preload()->native(false)->live()->required()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get): void {
                                $category = ExpenseCategory::query()->find($state);
                                if (! $category) return;
                                $set('tax_treatment', $category->default_tax_treatment);
                                $set('vat_rate', $category->default_tax_treatment === 'standard_vat' ? 16 : 0);
                                $set('withholding_tax_code', $category->default_wht_code);
                                $set(
                                    'withholding_tax_rate',
                                    static::whtRate(
                                        $category->default_wht_code,
                                        $get('supplier_residency')
                                    )
                                );
                                static::syncTotals($set, $get);
                            })
                            ->columnSpan(['default' => 12, 'md' => 5]),

                        Forms\Components\DatePicker::make('expense_date')
                            ->default(now('Africa/Nairobi'))->native(false)->required()
                            ->columnSpan(['default' => 12, 'md' => 2]),
                        Forms\Components\DatePicker::make('due_date')
                            ->native(false)->columnSpan(['default' => 12, 'md' => 2]),

                        Forms\Components\Textarea::make('description')
                            ->required()->rows(2)->columnSpanFull(),

                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier / Payee')
                            ->options(fn (): array => Supplier::query()->where('status', 'active')
                                ->orderBy('company_name')->pluck('company_name', 'id')->all())
                            ->searchable()->preload()->native(false)->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                $supplier = Supplier::query()->find($state);
                                if ($supplier) {
                                    $set('supplier_kra_pin', $supplier->kra_pin ?? null);
                                }
                            })
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Forms\Components\TextInput::make('supplier_invoice_number')
                            ->label('Supplier Invoice / Receipt No.')
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Forms\Components\TextInput::make('etims_invoice_number')
                            ->label('eTIMS Invoice / Control Number')
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\Select::make('cost_center_id')
                            ->label('Cost Centre')
                            ->options(fn (): array => AccountingCostCenter::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->preload()->native(false)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        Forms\Components\Select::make('project_fund_id')
                            ->label('Project Fund')
                            ->options(fn (): array => AccountingProjectFund::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->preload()->native(false)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                    ]),

                Forms\Components\Section::make('Kenya Tax Evidence')
                    ->description('Tax defaults are suggestions only. Confirm the actual supplier invoice, VAT registration, residency and withholding category before approval.')
                    ->icon('heroicon-o-document-check')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('supplier_kra_pin')
                            ->label('Supplier KRA PIN')->maxLength(30)
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\Select::make('supplier_residency')
                            ->options([
                                'resident' => 'Resident',
                                'non_resident' => 'Non-Resident',
                            ])
                            ->default('resident')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(
                                function (
                                    mixed $state,
                                    Forms\Set $set,
                                    Forms\Get $get
                                ): void {
                                    $set(
                                        'withholding_tax_rate',
                                        static::whtRate(
                                            $get('withholding_tax_code'),
                                            (string) $state
                                        )
                                    );

                                    static::syncTotals($set, $get);
                                }
                            )
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\Select::make('tax_treatment')
                            ->label('VAT Treatment')
                            ->options([
                                'standard_vat' => 'Standard VAT',
                                'zero_rated' => 'Zero Rated',
                                'exempt' => 'VAT Exempt',
                                'non_vat' => 'Outside VAT / Non-VAT',
                            ])->default('non_vat')->native(false)->live()->required()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get): void {
                                $set('vat_rate', $state === 'standard_vat' ? 16 : 0);
                                static::syncTotals($set, $get);
                            })
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\TextInput::make('vat_rate')
                            ->numeric()->suffix('%')->minValue(0)->maxValue(100)->default(0)->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => static::syncTotals($set, $get))
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\Toggle::make('vat_claimable')
                            ->label('Claim Input VAT')
                            ->helperText('Enable only when supported by a valid tax invoice and the purchase is deductible for VAT.')
                            ->default(false)->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\Select::make('withholding_tax_code')
                            ->label('Withholding Tax Type')
                            ->options([
                                'WHT_PROFESSIONAL' => 'Professional / Management / Training',
                                'WHT_RENT' => 'Commercial Rent',
                                'WHT_CONTRACTUAL' => 'Contractual Fees',
                            ])
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(
                                function (
                                    mixed $state,
                                    Forms\Set $set,
                                    Forms\Get $get
                                ): void {
                                    $set(
                                        'withholding_tax_rate',
                                        static::whtRate(
                                            filled($state)
                                                ? (string) $state
                                                : null,
                                            $get('supplier_residency')
                                        )
                                    );

                                    static::syncTotals($set, $get);
                                }
                            )
                            ->columnSpan(['default' => 12, 'md' => 5]),
                        Forms\Components\TextInput::make('withholding_tax_rate')
                            ->label('WHT Rate')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->live(onBlur: true)
                            ->helperText(
                                'The current configured rate is filled from '
                                . 'the WHT type and supplier residency. Confirm '
                                . 'the transaction classification before approval.'
                            )
                            ->afterStateUpdated(
                                fn (Forms\Set $set, Forms\Get $get) =>
                                    static::syncTotals($set, $get)
                            )
                            ->columnSpan(['default' => 12, 'md' => 4]),
                    ]),

                Forms\Components\Section::make('Amounts')
                    ->icon('heroicon-o-calculator')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('net_amount')
                            ->label('Amount Before VAT')->numeric()->minValue(0.01)->prefix('KES')->required()->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => static::syncTotals($set, $get))
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\TextInput::make('vat_amount')
                            ->label('VAT')->numeric()->prefix('KES')->readOnly()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\TextInput::make('withholding_tax_amount')
                            ->label('WHT Deducted')->numeric()->prefix('KES')->readOnly()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\TextInput::make('gross_amount')
                            ->label('Gross Invoice')->numeric()->prefix('KES')->readOnly()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Forms\Components\TextInput::make('payable_amount')
                            ->label('Payable to Supplier')->numeric()->prefix('KES')->readOnly()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Paid')->numeric()->prefix('KES')->readOnly()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Forms\Components\TextInput::make('balance_due')
                            ->label('Balance Due')->numeric()->prefix('KES')->readOnly()->dehydrated()
                            ->columnSpan(['default' => 12, 'md' => 4]),
                    ]),

                Forms\Components\Section::make('Supporting Evidence')
                    ->columns(12)
                    ->schema([
                        Forms\Components\FileUpload::make('receipt_path')
                            ->label('Receipt / Invoice')
                            ->directory('operating-expenses')->downloadable()
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        Forms\Components\Textarea::make('notes')->rows(3)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft', 'approved' => 'Approved', 'partially_paid' => 'Partially Paid',
                                'paid' => 'Paid', 'reversed' => 'Reversed',
                            ])->default('draft')->disabled()->dehydrated()->columnSpan(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('expense_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('expense_number')->label('Expense No.')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('expense_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->searchable()->badge()->color('info'),
                Tables\Columns\TextColumn::make('supplier.company_name')->label('Supplier')->searchable()->placeholder('Direct / Other'),
                Tables\Columns\TextColumn::make('description')->limit(35)->searchable(),
                Tables\Columns\TextColumn::make('gross_amount')->label('Gross')->money('KES')->sortable(),
                Tables\Columns\TextColumn::make('withholding_tax_amount')->label('WHT')->money('KES')->toggleable(),
                Tables\Columns\TextColumn::make('paid_amount')->money('KES')->color('success'),
                Tables\Columns\TextColumn::make('balance_due')->money('KES')->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('status_label')->label('Status')->badge()->color(fn (OperatingExpense $record): string => match ($record->status) {
                    'paid' => 'success', 'partially_paid' => 'warning', 'approved' => 'info', 'reversed' => 'danger', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('expense_category_id')->label('Category')->relationship('category', 'name')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('supplier_id')->label('Supplier')->relationship('supplier', 'company_name')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'approved' => 'Approved', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'reversed' => 'Reversed',
                ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (OperatingExpense $record): bool =>
                            static::permits(
                                'edit operating expenses'
                            )
                            && $record->isDraft()
                    ),
                Tables\Actions\Action::make('approvePost')
                    ->label('Approve & Post')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->visible(
                        fn (OperatingExpense $record): bool =>
                            static::permits(
                                'approve operating expenses'
                            )
                            && $record->isDraft()
                    )
                    ->action(function (OperatingExpense $record): void {
                        app(OperatingExpenseService::class)->approveAndPost($record);
                        Notification::make()->success()->title('Expense approved and posted')->send();
                    }),
                Tables\Actions\Action::make('pay')
                    ->label('Pay')->icon('heroicon-o-banknotes')->color('success')
                    ->visible(
                        fn (OperatingExpense $record): bool =>
                            static::permits(
                                'pay operating expenses'
                            )
                            && $record->isApproved()
                            && (float) $record->balance_due > 0
                    )
                    ->slideOver()->modalWidth('3xl')
                    ->form([
                        Forms\Components\TextInput::make('amount')->numeric()->prefix('KES')->required()->minValue(0.01)
                            ->default(fn (OperatingExpense $record): float => (float) $record->balance_due),
                        Forms\Components\DateTimePicker::make('payment_date')->default(now('Africa/Nairobi'))->seconds(false)->native(false)->required(),
                        Forms\Components\Select::make('payment_method')->options([
                            'bank' => 'Bank', 'mpesa' => 'M-Pesa', 'airtel_money' => 'Airtel Money', 'cash' => 'Cash', 'cheque' => 'Cheque',
                        ])->default('bank')->native(false)->live()->required(),
                        Forms\Components\TextInput::make('transaction_reference')->label('Transaction Reference')->required(),
                        Forms\Components\TextInput::make('mpesa_phone')->tel()
                            ->visible(fn (Forms\Get $get): bool => in_array($get('payment_method'), ['mpesa', 'airtel_money'], true)),
                        Forms\Components\TextInput::make('bank_name')->visible(fn (Forms\Get $get): bool => $get('payment_method') === 'bank'),
                        Forms\Components\Textarea::make('notes')->rows(2),
                    ])
                    ->action(function (OperatingExpense $record, array $data): void {
                        app(OperatingExpenseService::class)->recordAndPostPayment($record, $data);
                        Notification::make()->success()->title('Expense payment posted')->send();
                    }),
                Tables\Actions\Action::make('reverse')
                    ->label('Reverse Expense')->icon('heroicon-o-arrow-uturn-left')->color('danger')
                    ->visible(
                        fn (OperatingExpense $record): bool =>
                            static::permits(
                                'reverse operating expenses'
                            )
                            && $record->isApproved()
                            && ! $record->hasPostedPayments()
                    )
                    ->form([Forms\Components\Textarea::make('reason')->required()->minLength(5)])
                    ->action(function (OperatingExpense $record, array $data): void {
                        app(OperatingExpenseService::class)->reverseExpense($record, $data['reason']);
                        Notification::make()->warning()->title('Expense reversed')->send();
                    }),
                Tables\Actions\Action::make('voucher')
                    ->label('Voucher')->icon('heroicon-o-printer')->color('gray')
                    ->action(function (OperatingExpense $record) {
                        $record->load(['category.account', 'supplier', 'payments']);
                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.accounting.operating-expense-voucher', [
                            'expense' => $record, 'generatedBy' => auth()->user(),
                        ])->setPaper('a4');
                        return response()->streamDownload(fn () => print($pdf->output()), $record->expense_number . '.pdf');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (OperatingExpense $record): bool =>
                            static::permits(
                                'delete draft operating expenses'
                            )
                            && $record->isDraft()
                    ),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approveSelected')
                    ->label('Approve Selected Drafts')
                    ->visible(
                        fn (): bool => static::permits(
                            'approve operating expenses'
                        )
                    )->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $posted = 0;
                        foreach ($records as $record) {
                            if (! $record->isDraft()) continue;
                            app(OperatingExpenseService::class)->approveAndPost($record); $posted++;
                        }
                        Notification::make()->success()->title("{$posted} expense(s) approved and posted")->send();
                    })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('paySelectedFull')
                    ->label('Pay Selected in Full')
                    ->visible(
                        fn (): bool => static::permits(
                            'pay operating expenses'
                        )
                    )->icon('heroicon-o-banknotes')->color('warning')->requiresConfirmation()
                    ->form([
                        Forms\Components\DateTimePicker::make('payment_date')->default(now('Africa/Nairobi'))->seconds(false)->native(false)->required(),
                        Forms\Components\Select::make('payment_method')->options([
                            'bank' => 'Bank', 'mpesa' => 'M-Pesa', 'airtel_money' => 'Airtel Money', 'cash' => 'Cash', 'cheque' => 'Cheque',
                        ])->default('bank')->native(false)->required(),
                        Forms\Components\TextInput::make('transaction_reference')->label('Batch Reference Prefix')->required(),
                        Forms\Components\TextInput::make('bank_name'),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $paid = 0; $skipped = 0;
                        foreach ($records as $record) {
                            if (! $record->isApproved() || (float) $record->balance_due <= 0) { $skipped++; continue; }
                            app(OperatingExpenseService::class)->recordAndPostPayment($record, [
                                ...$data,
                                'amount' => (float) $record->balance_due,
                                'transaction_reference' => $data['transaction_reference'] . '-' . $record->expense_number,
                            ]);
                            $paid++;
                        }
                        Notification::make()->title("{$paid} expense(s) paid")
                            ->body("{$skipped} record(s) were not payable and were skipped.")
                            ->color($skipped ? 'warning' : 'success')->send();
                    })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('deleteDrafts')
                    ->label('Delete Selected Drafts')
                    ->visible(
                        fn (): bool => static::permits(
                            'delete draft operating expenses'
                        )
                    )->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->filter->isDraft()->each->delete())
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperatingExpenses::route('/'),
            'create' => Pages\CreateOperatingExpense::route('/create'),
            'edit' => Pages\EditOperatingExpense::route('/{record}/edit'),
        ];
    }
}
