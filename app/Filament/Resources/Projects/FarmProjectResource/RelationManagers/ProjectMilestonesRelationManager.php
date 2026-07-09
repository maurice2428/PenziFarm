<?php

namespace App\Filament\Resources\Projects\FarmProjectResource\RelationManagers;

use App\Services\Projects\ProjectFinancialService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectMilestonesRelationManager extends RelationManager
{
    protected static string $relationship = 'milestones';

    protected static ?string $title = 'Milestones';

    protected static ?string $modelLabel = 'Milestone';

    protected static ?string $pluralModelLabel = 'Milestones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Milestone Details')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Milestone Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'delayed' => 'Delayed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('progress_percent')
                            ->label('Progress %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0)
                            ->columnSpan(3),

                        Forms\Components\DatePicker::make('target_date')
                            ->label('Target Date')
                            ->native(false)
                            ->columnSpan(3),

                        Forms\Components\DatePicker::make('completed_at')
                            ->label('Completed Date')
                            ->native(false)
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('budget_amount')
                            ->label('Budget')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('spent_amount')
                            ->label('Spent')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
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
            ->defaultSort('target_date')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Milestone')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record): string => $record->description ? str($record->description)->limit(80)->toString() : ''),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?: 'pending')->replace('_', ' ')->headline())
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'info',
                        'delayed' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->suffix('%')
                    ->badge()
                    ->color(fn ($record): string => match (true) {
                        $record->progress_percent >= 90 => 'success',
                        $record->progress_percent >= 50 => 'info',
                        $record->progress_percent >= 20 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('target_date')
                    ->label('Target')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->date('d M Y')
                    ->placeholder('Pending')
                    ->sortable(),

                Tables\Columns\TextColumn::make('budget_amount')
                    ->label('Budget')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('spent_amount')
                    ->label('Spent')
                    ->money('KES')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'delayed' => 'Delayed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Milestone')
                    ->icon('heroicon-o-plus-circle')
                    ->after(fn () => $this->refreshProjectTotals()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->iconButton()
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

    protected function refreshProjectTotals(): void
    {
        app(ProjectFinancialService::class)->recalculate($this->ownerRecord);
        app(ProjectFinancialService::class)->recalculateProgress($this->ownerRecord);
    }
}
