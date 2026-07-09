<?php

namespace App\Filament\Resources\Projects\FarmProjectResource\RelationManagers;

use App\Services\Projects\ProjectFinancialService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectProgressUpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'progressUpdates';

    protected static ?string $title = 'Progress Updates';

    protected static ?string $modelLabel = 'Progress Update';

    protected static ?string $pluralModelLabel = 'Progress Updates';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Progress Update')
                    ->columns(12)
                    ->schema([
                        Forms\Components\DatePicker::make('update_date')
                            ->label('Update Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('title')
                            ->label('Update Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),

                        Forms\Components\Select::make('project_milestone_id')
                            ->label('Milestone')
                            ->options(fn ($livewire): array => $livewire->ownerRecord
                                ->milestones()
                                ->orderBy('target_date')
                                ->pluck('title', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('progress_percent')
                            ->label('Progress %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(0)
                            ->columnSpan(3),

                        Forms\Components\Select::make('weather_condition')
                            ->label('Weather')
                            ->options([
                                'sunny' => 'Sunny',
                                'cloudy' => 'Cloudy',
                                'rainy' => 'Rainy',
                                'windy' => 'Windy',
                                'muddy' => 'Muddy',
                                'normal' => 'Normal',
                            ])
                            ->searchable()
                            ->columnSpan(3),

                        Forms\Components\FileUpload::make('photo_path')
                            ->label('Progress Photo')
                            ->image()
                            ->directory('project-progress')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('work_done')
                            ->label('Work Done')
                            ->rows(3)
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('blockers')
                            ->label('Blockers / Challenges')
                            ->rows(3)
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('next_steps')
                            ->label('Next Steps')
                            ->rows(3)
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('narrative')
                            ->label('Narrative / Notes')
                            ->rows(3)
                            ->columnSpan(6),

                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => auth()->id()),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('update_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('update_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Update')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('milestone.title')
                    ->label('Milestone')
                    ->placeholder('General update')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->suffix('%')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('weather_condition')
                    ->label('Weather')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? str($state)->headline() : 'N/A')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('work_done')
                    ->label('Work Done')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Progress Update')
                    ->icon('heroicon-o-plus-circle')
                    ->after(fn () => $this->refreshProjectProgress()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->iconButton()
                    ->after(fn () => $this->refreshProjectProgress()),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete')
                    ->iconButton()
                    ->after(fn () => $this->refreshProjectProgress()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(fn () => $this->refreshProjectProgress()),
            ]);
    }

    protected function refreshProjectProgress(): void
    {
        app(ProjectFinancialService::class)->recalculateProgress($this->ownerRecord);
    }
}
