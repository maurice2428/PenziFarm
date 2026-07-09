<?php

namespace App\Filament\Resources\Projects\FarmProjectResource\RelationManagers;

use App\Services\Projects\ProjectFinancialService;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class ProjectTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Tasks';

    protected static ?string $modelLabel = 'Task';

    protected static ?string $pluralModelLabel = 'Tasks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Details')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Task Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),
                        Forms\Components\Select::make('project_milestone_id')
                            ->label('Milestone')
                            ->options(fn($livewire): array => $livewire
                                ->ownerRecord
                                ->milestones()
                                ->orderBy('target_date')
                                ->pluck('title', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->columnSpan(3),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->relationship('assignee', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(3),
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
                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('medium')
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
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('completed_at')
                            ->label('Completed Date')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn() => auth()->id()),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Task')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('milestone.title')
                    ->label('Milestone')
                    ->placeholder('No milestone')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => str($state ?: 'pending')->replace('_', ' ')->headline())
                    ->color(fn(?string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'info',
                        'delayed' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => str($state ?: 'medium')->headline())
                    ->color(fn(?string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->suffix('%')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->date('d M Y')
                    ->placeholder('Pending')
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn($query) => $query
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now('Africa/Nairobi'))
                        ->whereNotIn('status', ['completed', 'cancelled'])),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Task')
                    ->icon('heroicon-o-plus-circle')
                    ->after(fn() => $this->refreshProjectTotals()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->iconButton()
                    ->after(fn() => $this->refreshProjectTotals()),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete')
                    ->iconButton()
                    ->after(fn() => $this->refreshProjectTotals()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(fn() => $this->refreshProjectTotals()),
            ]);
    }

    protected function refreshProjectTotals(): void
    {
        app(ProjectFinancialService::class)->recalculate($this->ownerRecord);
        app(ProjectFinancialService::class)->recalculateProgress($this->ownerRecord);
    }
}
