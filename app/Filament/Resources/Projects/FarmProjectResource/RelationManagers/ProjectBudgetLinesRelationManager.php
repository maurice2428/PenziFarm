<?php

namespace App\Filament\Resources\Projects\FarmProjectResource\RelationManagers;

use App\Services\Projects\ProjectFinancialService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectBudgetLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetLines';

    protected static ?string $title = 'Budget Lines';

    protected static ?string $modelLabel = 'Budget Line';

    protected static ?string $pluralModelLabel = 'Budget Lines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Budget Item')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('cost_category')
                            ->label('Cost Category')
                            ->options([
                                'materials' => 'Materials',
                                'labour' => 'Labour',
                                'transport' => 'Transport',
                                'equipment' => 'Equipment',
                                'contractor' => 'Contractor',
                                'permits' => 'Permits / Licenses',
                                'fuel' => 'Fuel',
                                'professional_fees' => 'Professional Fees',
                                'repairs' => 'Repairs',
                                'other' => 'Other',
                            ])
                            ->default('other')
                            ->required()
                            ->searchable()
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('item_name')
                            ->label('Item Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(5),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'planned' => 'Planned',
                                'approved' => 'Approved',
                                'committed' => 'Committed',
                                'used' => 'Used',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('planned')
                            ->required()
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('unit')
                            ->label('Unit')
                            ->placeholder('bags, trips, pieces, days')
                            ->maxLength(50)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Unit Cost')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('estimated_amount')
                            ->label('Estimated Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('approved_amount')
                            ->label('Approved Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('actual_amount')
                            ->label('Actual Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('variance_amount')
                            ->label('Variance')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(3),

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
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('item_name')
                    ->label('Item')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record): string => $record->description ? str($record->description)->limit(80)->toString() : ''),

                Tables\Columns\TextColumn::make('cost_category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'other')->replace('_', ' ')->headline())
                    ->color('info'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('KES'),

                Tables\Columns\TextColumn::make('estimated_amount')
                    ->label('Estimated')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_amount')
                    ->label('Approved')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('actual_amount')
                    ->label('Actual')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('variance_amount')
                    ->label('Variance')
                    ->money('KES')
                    ->color(fn ($record): string => (float) $record->variance_amount < 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'planned')->replace('_', ' ')->headline())
                    ->color(fn (?string $state): string => match ($state) {
                        'approved' => 'success',
                        'committed' => 'info',
                        'used' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cost_category')
                    ->label('Cost Category')
                    ->options([
                        'materials' => 'Materials',
                        'labour' => 'Labour',
                        'transport' => 'Transport',
                        'equipment' => 'Equipment',
                        'contractor' => 'Contractor',
                        'permits' => 'Permits / Licenses',
                        'fuel' => 'Fuel',
                        'professional_fees' => 'Professional Fees',
                        'repairs' => 'Repairs',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'planned' => 'Planned',
                        'approved' => 'Approved',
                        'committed' => 'Committed',
                        'used' => 'Used',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Budget Line')
                    ->icon('heroicon-o-plus-circle')
                    ->mutateFormDataUsing(fn (array $data): array => $this->prepareBudgetData($data))
                    ->after(fn () => $this->refreshProjectTotals()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->iconButton()
                    ->mutateFormDataUsing(fn (array $data): array => $this->prepareBudgetData($data))
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

    protected function prepareBudgetData(array $data): array
    {
        $quantity = (float) ($data['quantity'] ?? 0);
        $unitCost = (float) ($data['unit_cost'] ?? 0);

        if ((float) ($data['estimated_amount'] ?? 0) <= 0 && $quantity > 0 && $unitCost > 0) {
            $data['estimated_amount'] = $quantity * $unitCost;
        }

        if ((float) ($data['approved_amount'] ?? 0) <= 0) {
            $data['approved_amount'] = $data['estimated_amount'] ?? 0;
        }

        $data['variance_amount'] = (float) ($data['approved_amount'] ?? 0) - (float) ($data['actual_amount'] ?? 0);

        return $data;
    }

    protected function refreshProjectTotals(): void
    {
        app(ProjectFinancialService::class)->recalculate($this->ownerRecord);
        app(ProjectFinancialService::class)->recalculateProgress($this->ownerRecord);
    }
}
