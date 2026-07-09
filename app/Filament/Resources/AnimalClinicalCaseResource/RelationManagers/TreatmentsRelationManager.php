<?php

namespace App\Filament\Resources\AnimalClinicalCaseResource\RelationManagers;

use App\Models\AnimalTreatmentRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TreatmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'treatments';

    protected static ?string $title = 'Treatment Records';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('animal_id')
                ->default(fn (): int => $this->getOwnerRecord()->animal_id)
                ->required(),

            Forms\Components\DateTimePicker::make('given_at')
                ->label('Treatment Date & Time')
                ->default(fn () => now())
                ->seconds(false)
                ->required(),

            Forms\Components\TextInput::make('medicine_name')
                ->label('Medicine / Drug Used')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('dosage')
                ->placeholder('Example: 5 ml'),

            Forms\Components\TextInput::make('method')
                ->label('Route / Method')
                ->placeholder('Example: Oral, Injection, Topical'),

            Forms\Components\TextInput::make('frequency')
                ->placeholder('Example: Twice daily'),

            Forms\Components\TextInput::make('duration')
                ->placeholder('Example: 3 days'),

            Forms\Components\TextInput::make('quantity_used')
                ->numeric()
                ->suffix('Units'),

            Forms\Components\Select::make('status')
                ->options(AnimalTreatmentRecord::statuses())
                ->default('Completed')
                ->required(),

            Forms\Components\TextInput::make('administered_by')
                ->label('Attendant / Officer')
                ->default(fn () => auth()->user()?->name)
                ->maxLength(255),

            Forms\Components\DatePicker::make('follow_up_date')
                ->label('Follow-up Date'),

            Forms\Components\Textarea::make('notes')
                ->label('Remarks')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('given_at', 'desc')
            ->recordTitleAttribute('medicine_name')
            ->columns([
                Tables\Columns\TextColumn::make('given_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('medicine_name')
                    ->label('Medicine')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('dosage'),

                Tables\Columns\TextColumn::make('method')
                    ->label('Method'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'Planned' => 'info',
                        'Follow-up Required' => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('administered_by')
                    ->label('Attendant'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Treatment'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
