<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\SalesInvoiceResource\Pages;
use App\Models\Sales\IncomeCategory;
use App\Models\Sales\SalesInvoice;
use App\Models\Animal;
use App\Models\AnimalWeight;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesInvoiceResource extends Resource
{
    protected static ?string $model = SalesInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?string $modelLabel = 'Sales Invoice';

    protected static ?string $pluralModelLabel = 'Sales Invoices';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getRelations(): array
    {
        return [
            SalesInvoiceResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view sales invoices') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create sales invoices') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit sales invoices') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete sales invoices') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete sales invoices') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore sales invoices') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore sales invoices') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete sales invoices') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete sales invoices') ?? false;
    }

    public static function calculateLineTotal(Get $get, Set $set): void
    {
        $weight = (float) ($get('sale_weight') ?? 0);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $premium = (float) ($get('breeder_premium_amount') ?? 0);
        $priceMode = $get('price_mode') ?? 'fixed';

        $lineTotal = $priceMode === 'per_kg'
            ? $weight * $unitPrice
            : $unitPrice;

        $set('line_total', round($lineTotal + $premium, 2));
    }

    public static function recalculateRepeaterTotals(Set $set, Get $get): void
    {
        $items = $get('../../items') ?? [];

        $totalAnimals = 0;
        $totalWeight = 0;
        $subtotal = 0;

        foreach ($items as $item) {
            if (empty($item['animal_id'])) {
                continue;
            }

            $totalAnimals++;

            $weight = (float) ($item['sale_weight'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $premium = (float) ($item['breeder_premium_amount'] ?? 0);
            $priceMode = $item['price_mode'] ?? 'fixed';

            $lineTotal = $priceMode === 'per_kg'
                ? $weight * $unitPrice
                : $unitPrice;

            $lineTotal += $premium;

            $totalWeight += $weight;
            $subtotal += $lineTotal;
        }

        $discount = (float) ($get('../../discount_amount') ?? 0);
        $tax = (float) ($get('../../tax_amount') ?? 0);
        $other = (float) ($get('../../other_charges_amount') ?? 0);
        $paid = (float) ($get('../../amount_paid') ?? 0);

        $grandTotal = max(0, $subtotal - $discount + $tax + $other);

        $set('../../total_animals', $totalAnimals);
        $set('../../total_weight', round($totalWeight, 2));
        $set('../../average_weight', $totalAnimals > 0 ? round($totalWeight / $totalAnimals, 2) : 0);
        $set('../../subtotal', round($subtotal, 2));
        $set('../../grand_total', round($grandTotal, 2));
        $set('../../balance_due', round(max(0, $grandTotal - $paid), 2));
    }

    public static function recalculateRootTotals(Set $set, Get $get): void
    {
        $items = $get('items') ?? [];

        $totalAnimals = 0;
        $totalWeight = 0;
        $subtotal = 0;

        foreach ($items as $item) {
            if (empty($item['animal_id'])) {
                continue;
            }

            $totalAnimals++;

            $weight = (float) ($item['sale_weight'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $premium = (float) ($item['breeder_premium_amount'] ?? 0);
            $priceMode = $item['price_mode'] ?? 'fixed';

            $lineTotal = $priceMode === 'per_kg'
                ? $weight * $unitPrice
                : $unitPrice;

            $lineTotal += $premium;

            $totalWeight += $weight;
            $subtotal += $lineTotal;
        }

        $discount = (float) ($get('discount_amount') ?? 0);
        $tax = (float) ($get('tax_amount') ?? 0);
        $other = (float) ($get('other_charges_amount') ?? 0);
        $paid = (float) ($get('amount_paid') ?? 0);

        $grandTotal = max(0, $subtotal - $discount + $tax + $other);

        $set('total_animals', $totalAnimals);
        $set('total_weight', round($totalWeight, 2));
        $set('average_weight', $totalAnimals > 0 ? round($totalWeight / $totalAnimals, 2) : 0);
        $set('subtotal', round($subtotal, 2));
        $set('grand_total', round($grandTotal, 2));
        $set('balance_due', round(max(0, $grandTotal - $paid), 2));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->description('Create the main invoice document for animal or farm sales.')
                    ->icon('heroicon-o-document-text')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto generated')
                            ->prefixIcon('heroicon-o-hashtag'),
                        /* Forms\Components\Select::make('customer_id')
                             ->label('Customer')
                             ->relationship('customer', 'name')
                             ->searchable()
                             ->preload()
                             ->required()
                             ->createOptionForm([
                                 Forms\Components\TextInput::make('name')
                                     ->label('Customer Name')
                                     ->required()
                                     ->prefixIcon('heroicon-o-user'),

                                 Forms\Components\TextInput::make('phone')
                                     ->label('Phone')
                                     ->tel()
                                     ->prefixIcon('heroicon-o-phone'),

                                 Forms\Components\TextInput::make('email')
                                     ->label('Email')
                                     ->email()
                                     ->prefixIcon('heroicon-o-envelope'),

                                 Forms\Components\TextInput::make('kra_pin')
                                     ->label('KRA PIN')
                                     ->prefixIcon('heroicon-o-identification'),

                                 Forms\Components\Select::make('customer_type')
                                     ->label('Customer Type')
                                     ->options([
                                         'individual' => 'Individual',
                                         'company' => 'Company',
                                         'farm' => 'Farm',
                                         'butcher' => 'Butcher',
                                         'broker' => 'Broker',
                                         'institution' => 'Institution',
                                         'other' => 'Other',
                                     ])
                                     ->default('individual')
                                     ->required()
                                     ->prefixIcon('heroicon-o-users'),

                                 Forms\Components\TextInput::make('county')
                                     ->label('County')
                                     ->prefixIcon('heroicon-o-map'),

                                 Forms\Components\TextInput::make('town')
                                     ->label('Town')
                                     ->prefixIcon('heroicon-o-map-pin'),

                                 Forms\Components\Textarea::make('address')
                                     ->label('Address')
                                     ->rows(2)
                                     ->columnSpanFull(),
                             ])
                             ->prefixIcon('heroicon-o-user-circle'),*/
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select an existing customer or create a new one.')
                            ->prefixIcon('heroicon-o-user-circle')
                            ->suffixActions([
                                Forms\Components\Actions\Action::make('createCustomer')
                                    ->label('New Customer')
                                    ->icon('heroicon-o-plus-circle')
                                    ->color('success')
                                    // ->url(fn () => url('/admin/sales/customers/create?return_url=' . urlencode(url('/admin/sales/sales-invoices'))))
                                    ->url(fn() => url('/admin/sales/customers/create?return_url=' . urlencode(
                                        url('/admin/sales/sales-invoices')
                                    )))
                                    ->openUrlInNewTab(false),
                                Forms\Components\Actions\Action::make('refreshCustomers')
                                    ->label('Refresh')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('gray')
                                    ->action(fn() => null),
                            ]),
                        Forms\Components\Select::make('income_category_id')
                            ->label('Income Category')
                            ->options(fn() => IncomeCategory::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-banknotes'),
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label('Invoice Date')
                            ->default(now('Africa/Nairobi'))
                            ->required()
                            ->prefixIcon('heroicon-o-calendar-days'),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->prefixIcon('heroicon-o-clock'),
                        Forms\Components\Select::make('sale_type')
                            ->label('Sale Type')
                            ->options([
                                'general' => 'General',
                                'breeder' => 'Breeder',
                                'slaughter' => 'Slaughter',
                                'commercial' => 'Commercial',
                                'cull' => 'Cull',
                                'export' => 'Export',
                                'other' => 'Other',
                            ])
                            ->default('general')
                            ->required()
                            ->prefixIcon('heroicon-o-tag'),
                        Forms\Components\Select::make('status')
                            ->label('Invoice Status')
                            ->options([
                                'draft' => 'Draft',
                                'issued' => 'Issued',
                                'approved' => 'Approved',
                                'cancelled' => 'Cancelled',
                                'voided' => 'Voided',
                            ])
                            ->default('draft')
                            ->required()
                            ->prefixIcon('heroicon-o-check-badge'),
                    ]),
                Forms\Components\Section::make('Animal / Sale Items')
                    ->description('Each selected animal becomes one invoice item. Weight is pulled from the latest animal weight record.')
                    ->icon('heroicon-o-queue-list')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->label('Invoice Items')
                            ->addActionLabel('Add Animal')
                            ->reorderable(false)
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 4,
                            ])
                            ->schema([
                                Forms\Components\Select::make('animal_id')
                                    ->label('Animal')
                                    ->options(fn() => Animal::query()
                                        ->where('status', 'Active')
                                        ->orderBy('tag_number')
                                        ->pluck('tag_number', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $animal = Animal::query()
                                            ->with('breed')
                                            ->find($state);

                                        if (!$animal) {
                                            return;
                                        }

                                        $latestWeight = AnimalWeight::query()
                                            ->where('animal_id', $animal->id)
                                            ->whereNull('deleted_at')
                                            ->latest('recorded_at')
                                            ->value('weight_kg') ?? 0;

                                        $set('tag_number', $animal->tag_number);
                                        $set('breed_id', $animal->breed_id);
                                        $set('breed_name', $animal->breed?->breed_name ?? '-');
                                        $set('sex', $animal->sex);
                                        $set('sale_weight', $latestWeight);
                                        $set('quantity', 1);
                                        $set('description', 'Animal sale: ' . $animal->tag_number);

                                        self::calculateLineTotal($get, $set);
                                        self::recalculateRepeaterTotals($set, $get);
                                    })
                                    ->prefixIcon('heroicon-o-identification'),
                                Forms\Components\TextInput::make('tag_number')
                                    ->label('Tag Number')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-hashtag'),
                                Forms\Components\TextInput::make('breed_name')
                                    ->label('Breed')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-squares-2x2'),
                                Forms\Components\TextInput::make('sex')
                                    ->label('Sex')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-user'),
                                Forms\Components\Hidden::make('breed_id'),
                                Forms\Components\Hidden::make('quantity')
                                    ->default(1)
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('description')
                                    ->dehydrated(),
                                Forms\Components\Select::make('price_mode')
                                    ->label('Price Mode')
                                    ->options([
                                        'fixed' => 'Fixed Price',
                                        'per_kg' => 'Price Per KG',
                                        'breeder' => 'Breeder Price',
                                        'manual' => 'Manual',
                                    ])
                                    ->default('fixed')
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        self::calculateLineTotal($get, $set);
                                        self::recalculateRepeaterTotals($set, $get);
                                    })
                                    ->prefixIcon('heroicon-o-calculator'),
                                Forms\Components\TextInput::make('sale_weight')
                                    ->label('Sale Weight / KG')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->default(0)
                                    ->prefixIcon('heroicon-o-scale'),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label(fn(Get $get) => $get('price_mode') === 'per_kg' ? 'Price Per KG' : 'Animal Price')
                                    ->numeric()
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->required()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        self::calculateLineTotal($get, $set);
                                        self::recalculateRepeaterTotals($set, $get);
                                    })
                                    ->prefixIcon('heroicon-o-currency-dollar'),
                                Forms\Components\TextInput::make('breeder_premium_amount')
                                    ->label('Breeder Premium')
                                    ->numeric()
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        self::calculateLineTotal($get, $set);
                                        self::recalculateRepeaterTotals($set, $get);
                                    })
                                    ->prefixIcon('heroicon-o-sparkles'),
                                Forms\Components\TextInput::make('line_total')
                                    ->label('Line Total')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->default(0)
                                    ->prefixIcon('heroicon-o-banknotes'),
                                Forms\Components\Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Forms\Components\Section::make('Totals & Charges')
                    ->description('Invoice totals update automatically from selected animals, weights, prices, and charges.')
                    ->icon('heroicon-o-calculator')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('total_animals')
                            ->label('Total Animals')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->prefixIcon('heroicon-o-cube'),
                        Forms\Components\TextInput::make('total_weight')
                            ->label('Total Weight')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->prefixIcon('heroicon-o-scale'),
                        Forms\Components\TextInput::make('average_weight')
                            ->label('Average Weight')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->prefixIcon('heroicon-o-chart-bar'),
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->prefixIcon('heroicon-o-banknotes'),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Discount')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::recalculateRootTotals($set, $get))
                            ->prefixIcon('heroicon-o-receipt-refund'),
                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Tax Amount')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::recalculateRootTotals($set, $get))
                            ->prefixIcon('heroicon-o-receipt-percent'),
                        Forms\Components\TextInput::make('other_charges_amount')
                            ->label('Other Charges')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::recalculateRootTotals($set, $get))
                            ->prefixIcon('heroicon-o-plus-circle'),
                        Forms\Components\TextInput::make('grand_total')
                            ->label('Grand Total')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->prefixIcon('heroicon-o-currency-dollar'),
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Paid')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::recalculateRootTotals($set, $get))
                            ->prefixIcon('heroicon-o-wallet'),
                        Forms\Components\TextInput::make('balance_due')
                            ->label('Balance Due')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->prefixIcon('heroicon-o-exclamation-circle'),
                        Forms\Components\Textarea::make('other_charges_description')
                            ->label('Other Charges Description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('terms')
                            ->label('Terms')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('invoice_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-document-text'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-user-circle'),
                Tables\Columns\TextColumn::make('sale_type_label')
                    ->label('Sale Type')
                    ->badge()
                    ->icon('heroicon-o-tag'),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                Tables\Columns\TextColumn::make('total_animals')
                    ->label('Animals')
                    ->sortable()
                    ->icon('heroicon-o-cube'),
                Tables\Columns\TextColumn::make('total_weight')
                    ->label('Weight')
                    ->suffix(' KG')
                    ->sortable()
                    ->icon('heroicon-o-scale'),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('KES')
                    ->sortable()
                    ->icon('heroicon-o-banknotes'),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('KES')
                    ->sortable()
                    ->icon('heroicon-o-wallet'),
                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('KES')
                    ->sortable()
                    ->icon('heroicon-o-exclamation-circle'),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->icon('heroicon-o-check-badge'),
                Tables\Columns\TextColumn::make('payment_status_label')
                    ->label('Payment')
                    ->badge()
                    ->icon('heroicon-o-credit-card'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('d M Y, h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sale_type')
                    ->label('Sale Type')
                    ->options([
                        'general' => 'General',
                        'breeder' => 'Breeder',
                        'slaughter' => 'Slaughter',
                        'commercial' => 'Commercial',
                        'cull' => 'Cull',
                        'export' => 'Export',
                        'other' => 'Other',
                    ]),
                SelectFilter::make('status')
                    ->label('Invoice Status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'approved' => 'Approved',
                        'cancelled' => 'Cancelled',
                        'voided' => 'Voided',
                    ]),
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'overpaid' => 'Overpaid',
                        'refunded' => 'Refunded',
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn() => auth()->user()?->can('view sales invoices') ?? false),
                Tables\Actions\Action::make('printInvoice')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->visible(fn() => auth()->user()?->can('print sales invoices') ?? false)
                    ->action(function (SalesInvoice $record) {
                        $record->load(['customer', 'incomeCategory', 'items', 'createdBy']);

                        $pdf = Pdf::loadView('pdfs.sales.sales-invoice', [
                            'invoice' => $record,
                            'generatedBy' => auth()->user(),
                            'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
                        ])->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn() => print ($pdf->output()),
                            $record->invoice_number . '.pdf'
                        );
                    }),
                Tables\Actions\Action::make('gatePass')
                    ->label('Gate Pass')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->visible(fn(SalesInvoice $record): bool =>
                        !$record->trashed() &&
                        !in_array($record->status, ['cancelled', 'voided'], true) &&
                        (auth()->user()?->can('print sales gatepasses') ?? false))
                    ->action(function (SalesInvoice $record) {
                        $record->load([
                            'customer',
                            'items',
                            'createdBy',
                        ]);

                        if ($record->items->isEmpty()) {
                            Notification::make()
                                ->danger()
                                ->title('Gate pass cannot be generated')
                                ->body('This invoice has no animal items.')
                                ->send();

                            return null;
                        }

                        $pdf = Pdf::loadView('pdfs.sales.sales-gate-pass', [
                            'invoice' => $record,
                            'generatedBy' => auth()->user(),
                            'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
                        ])->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn() => print ($pdf->output()),
                            'gate-pass-' . $record->invoice_number . '.pdf'
                        );
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) =>
                        !$record->trashed() &&
                        (auth()->user()?->can('edit sales invoices') ?? false)),
                /*Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) =>
                        !$record->trashed() &&
                        (auth()->user()?->can('delete sales invoices') ?? false)),*/
                Tables\Actions\DeleteAction::make()
                    ->label('Delete Invoice')
                    ->modalHeading('Delete Sales Invoice')
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('danger')
                    ->modalDescription(function (SalesInvoice $record): string {
                        $paymentsCount = $record->payments()->whereNull('deleted_at')->count();

                        if ($paymentsCount > 0) {
                            return "This invoice has {$paymentsCount} payment record(s). You cannot delete an invoice that already has payments. Delete or reverse the payments first.";
                        }

                        return 'Deleting this invoice will return all linked animals back to Active status.';
                    })
                    ->requiresConfirmation()
                    ->before(function (SalesInvoice $record, Tables\Actions\DeleteAction $action) {
                        if ($record->payments()->whereNull('deleted_at')->exists()) {
                            Notification::make()
                                ->title('Invoice cannot be deleted')
                                ->body('This invoice has payment records. Delete or reverse the payments first before deleting the invoice.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->after(function (SalesInvoice $record) {
                        $record
                            ->items()
                            ->whereNotNull('animal_id')
                            ->pluck('animal_id')
                            ->each(function ($animalId) {
                                Animal::whereKey($animalId)->update([
                                    'status' => 'Active',
                                ]);
                            });
                    })
                    ->visible(fn($record) =>
                        !$record->trashed() &&
                        (auth()->user()?->can('delete sales invoices') ?? false)),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn($record) =>
                        $record->trashed() &&
                        (auth()->user()?->can('restore sales invoices') ?? false)),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn($record) =>
                        $record->trashed() &&
                        (auth()->user()?->can('force delete sales invoices') ?? false)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('delete sales invoices') ?? false),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('restore sales invoices') ?? false),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('force delete sales invoices') ?? false),
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
            'index' => Pages\ListSalesInvoices::route('/'),
            'create' => Pages\CreateSalesInvoice::route('/create'),
            'view' => Pages\ViewSalesInvoice::route('/{record}'),
            'edit' => Pages\EditSalesInvoice::route('/{record}/edit'),
        ];
    }
}
