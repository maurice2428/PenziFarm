<?php

namespace App\Filament\Resources\AnimalClinicalCaseResource\RelationManagers;

use App\Filament\Resources\AnimalLabRequestResource;
use App\Models\AnimalLabRequest;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class LabRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'labRequests';

    protected static ?string $title = 'Laboratory Requests';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('animal_id')
                ->default(fn(): int => $this->getOwnerRecord()->animal_id)
                ->required(),
            Forms\Components\Select::make('status')
                ->options(AnimalLabRequest::statuses())
                ->default('Requested')
                ->required(),
            Forms\Components\DateTimePicker::make('requested_at')
                ->default(fn() => now())
                ->seconds(false)
                ->required(),
            Forms\Components\TextInput::make('clinic_name')
                ->label('Veterinary Clinic / Laboratory')
                ->required(),
            Forms\Components\CheckboxList::make('specimens')
                ->label('Specimen Collected')
                ->options(AnimalLabRequestResource::specimenOptions())
                ->columns(2)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Select::make('testing_purpose')
                ->options(AnimalLabRequestResource::purposeOptions())
                ->required(),
            Forms\Components\CheckboxList::make('requested_tests')
                ->label('Tests Requested')
                ->options(AnimalLabRequestResource::testOptions())
                ->columns(2)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('clinical_signs')
                ->label('Clinical Signs')
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('length_of_illness'),
            Forms\Components\TextInput::make('temperature_c')
                ->numeric()
                ->step(0.1)
                ->suffix('°C'),
            Forms\Components\TextInput::make('attending_officer')
                ->default(fn() => auth()->user()?->name),
            Forms\Components\Textarea::make('notes')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->recordTitleAttribute('request_number')
            ->columns([
                Tables\Columns\TextColumn::make('request_number')
                    ->label('Request No.')
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('clinic_name')
                    ->label('Laboratory'),
                Tables\Columns\TextColumn::make('requested_tests_text')
                    ->label('Tests')
                    ->limit(45),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Requested')
                    ->dateTime('d M Y H:i'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Lab Request'),
            ])
            ->actions([
                Tables\Actions\Action::make('openLabRequestPdf')
                    ->icon('heroicon-m-document-arrow-down')
                    ->iconButton()
                    ->tooltip('Generate laboratory request PDF')
                    ->color('danger')
                    ->action(function (AnimalLabRequest $record) {
                        return redirect()->route(
                            'animal-lab-requests.pdf',
                            ['labRequest' => $record->getKey()]
                        );
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
