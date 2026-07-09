<?php

namespace App\Filament\Resources\Projects\FarmProjectResource\RelationManagers;

use App\Services\Projects\ProjectFinancialService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Expenses';

    protected static ?string $modelLabel = 'Expense';

    protected static ?string $pluralModelLabel = 'Expenses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Expense Details')
                    ->columns(12)
                    ->schema([
                        Forms\Components\DatePicker::make('expense_date')
                            ->label('Expense Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\Select::make('project_budget_line_id')
                            ->label('Budget Line')
                            ->options(fn ($livewire): array => $livewire->ownerRecord
                                ->budgetLines()
                                ->orderBy('item_name')
                                ->pluck('item_name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->columnSpan(4),

                        Forms\Components\Select::make('expense_type')
                            ->label('Expense Type')
                            ->options([
                                'materials' => 'Materials',
                                'labour' => 'Labour',
                                'transport' => 'Transport',
                                'equipment' => 'Equipment',
                                'contractor' => 'Contractor',
                                'fuel' => 'Fuel',
                                'permit' => 'Permit',
                                'professional_fee' => 'Professional Fee',
                                'repair' => 'Repair',
                                'other' => 'Other',
                            ])
                            ->default('other')
                            ->searchable()
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('reference_no')
                            ->label('Reference No.')
                            ->maxLength(100)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('payee')
                            ->label('Payee / Supplier')
                            ->maxLength(255)
                            ->columnSpan(4),

                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'bank' => 'Bank',
                                'mpesa' => 'M-Pesa',
                                'cheque' => 'Cheque',
                                'credit' => 'Credit',
                                'other' => 'Other',
                            ])
                            ->default('cash')
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'paid' => 'Paid',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('unit')
                            ->label('Unit')
                            ->placeholder('trips, bags, days, pieces')
                            ->maxLength(50)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Unit Cost')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Tax')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\FileUpload::make('receipt_path')
                            ->label('Receipt / Attachment')
                            ->directory('project-receipts')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => auth()->id()),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('expense_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expense_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'other')->replace('_', ' ')->headline())
                    ->color('info'),

                Tables\Columns\TextColumn::make('payee')
                    ->label('Payee')
                    ->searchable()
                    ->placeholder('N/A')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(45)
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'cash')->headline())
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'pending')->replace('_', ' ')->headline())
                    ->color(fn (?string $state): string => match ($state) {
                        'approved', 'paid' => 'success',
                        'pending' => 'warning',
                        'rejected', 'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reference_no')
                    ->label('Ref')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('expense_type')
                    ->label('Expense Type')
                    ->options([
                        'materials' => 'Materials',
                        'labour' => 'Labour',
                        'transport' => 'Transport',
                        'equipment' => 'Equipment',
                        'contractor' => 'Contractor',
                        'fuel' => 'Fuel',
                        'permit' => 'Permit',
                        'professional_fee' => 'Professional Fee',
                        'repair' => 'Repair',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'bank' => 'Bank',
                        'mpesa' => 'M-Pesa',
                        'cheque' => 'Cheque',
                        'credit' => 'Credit',
                        'other' => 'Other',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Expense')
                    ->icon('heroicon-o-plus-circle')
                    ->mutateFormDataUsing(fn (array $data): array => $this->prepareExpenseData($data))
                    ->after(fn () => $this->refreshProjectTotals()),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('')
                    ->tooltip('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->iconButton()
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->action(function ($record): void {
                        $record->forceFill([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now('Africa/Nairobi'),
                        ])->save();

                        $this->refreshProjectTotals();

                        Notification::make()
                            ->title('Expense approved')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->iconButton()
                    ->mutateFormDataUsing(fn (array $data): array => $this->prepareExpenseData($data))
                    ->after(fn () => $this->refreshProjectTotals()),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete')
                    ->iconButton()
                    ->after(fn () => $this->refreshProjectTotals()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(fn () => $this->refreshProjectTotals()),
            ]);
    }

    protected function prepareExpenseData(array $data): array
    {
        $quantity = (float) ($data['quantity'] ?? 0);
        $unitCost = (float) ($data['unit_cost'] ?? 0);

        if ((float) ($data['amount'] ?? 0) <= 0 && $quantity > 0 && $unitCost > 0) {
            $data['amount'] = $quantity * $unitCost;
        }

        $data['tax_amount'] = (float) ($data['tax_amount'] ?? 0);

        if ((float) ($data['total_amount'] ?? 0) <= 0) {
            $data['total_amount'] = (float) ($data['amount'] ?? 0) + (float) ($data['tax_amount'] ?? 0);
        }

        return $data;
    }

    protected function refreshProjectTotals(): void
    {
        app(ProjectFinancialService::class)->recalculate($this->ownerRecord);
        app(ProjectFinancialService::class)->recalculateProgress($this->ownerRecord);
    }
}
