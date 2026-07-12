<?php

namespace App\Filament\Resources\HR\EmployeeResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static ?string $title = 'Employment Movement History';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('movement_type')
            ->defaultSort('effective_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('effective_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('movement_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString())
                    ->color(fn (string $state): string => match ($state) {
                        'promotion', 'reinstatement' => 'success',
                        'demotion', 'suspension' => 'warning',
                        'termination' => 'danger',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('fromJobTitle.name')->label('From Role')->placeholder('—'),
                Tables\Columns\TextColumn::make('toJobTitle.name')->label('To Role')->placeholder('—'),
                Tables\Columns\TextColumn::make('from_basic_salary')->label('Old Salary')->money('KES')->toggleable(),
                Tables\Columns\TextColumn::make('to_basic_salary')->label('New Salary')->money('KES')->toggleable(),
                Tables\Columns\TextColumn::make('previous_status')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('new_status')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('reason')->limit(60)->wrap(),
                Tables\Columns\TextColumn::make('approver.name')->label('Approved By')->placeholder('System'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')->disabled()->columnSpanFull(),
                        \Filament\Forms\Components\Textarea::make('notes')->disabled()->columnSpanFull(),
                    ]),
            ])
            ->bulkActions([]);
    }
}
