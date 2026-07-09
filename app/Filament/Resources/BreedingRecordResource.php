<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BreedingRecordResource\Pages;
use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Services\Breeding\BreedingDeliveryService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class BreedingRecordResource extends Resource
{
    protected static ?string $model = BreedingRecord::class;

    protected static ?string $navigationGroup = 'Breeding Management';

    protected static ?string $navigationLabel = 'Breeding Records';

    protected static ?string $modelLabel = 'Breeding Record';

    protected static ?string $pluralModelLabel = 'Breeding Records';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view breeding records') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit breeding records') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Breeding Reference')
                    ->description('Core breeding information generated from the breeding batch.')
                    ->icon('heroicon-o-heart')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Placeholder::make('batch_number_display')
                            ->label('Batch No.')
                            ->content(function (?BreedingRecord $record): string {
                                $record?->loadMissing('batch');

                                return $record?->batch?->batch_number ?? '-';
                            })
                            ->columnSpan(3),
                        Forms\Components\Placeholder::make('female_display')
                            ->label('Female / Dam')
                            ->content(function (?BreedingRecord $record): string {
                                $record?->loadMissing('female');

                                return $record?->female?->tag_number ?? '-';
                            })
                            ->columnSpan(3),
                        Forms\Components\Placeholder::make('male_display')
                            ->label('Male / Sire')
                            ->content(function (?BreedingRecord $record): string {
                                $record?->loadMissing('male');

                                return $record?->male?->tag_number ?? '-';
                            })
                            ->columnSpan(3),
                        Forms\Components\Placeholder::make('species_display')
                            ->label('Species')
                            ->content(fn(?BreedingRecord $record): string =>
                                $record?->species ?: '-')
                            ->columnSpan(3),
                        Forms\Components\Placeholder::make('female_breed_display')
                            ->label('Female Breed')
                            ->content(function (?BreedingRecord $record): string {
                                $record?->loadMissing(['femaleBreed', 'female.breed']);

                                return $record?->femaleBreed?->breed_name
                                    ?? $record?->female?->breed?->breed_name
                                    ?? '-';
                            })
                            ->columnSpan(3),
                        Forms\Components\Placeholder::make('male_breed_display')
                            ->label('Male Breed')
                            ->content(function (?BreedingRecord $record): string {
                                $record?->loadMissing(['maleBreed', 'male.breed']);

                                return $record?->maleBreed?->breed_name
                                    ?? $record?->male?->breed?->breed_name
                                    ?? '-';
                            })
                            ->columnSpan(3),
                        Forms\Components\Placeholder::make('mating_date_display')
                            ->label('Mating Date')
                            ->content(function (?BreedingRecord $record): string {
                                if (!$record?->mating_date) {
                                    return '-';
                                }

                                return \Carbon\Carbon::parse($record->mating_date)->format('d M Y');
                            })
                            ->columnSpan(3),
                        Forms\Components\Placeholder::make('expected_due_date_display')
                            ->label('Expected Due Date')
                            ->content(function (?BreedingRecord $record): string {
                                if (!$record?->expected_due_date) {
                                    return '-';
                                }

                                return \Carbon\Carbon::parse($record->expected_due_date)->format('d M Y');
                            })
                            ->columnSpan(3),
                        Forms\Components\Placeholder::make('gestation_days_display')
                            ->label('Gestation Days')
                            ->content(fn(?BreedingRecord $record): string =>
                                $record?->gestation_days
                                    ? number_format((int) $record->gestation_days) . ' days'
                                    : '-')
                            ->columnSpan(3),
                        Forms\Components\Placeholder::make('inbreeding_status_display')
                            ->label('Inbreeding Status')
                            ->content(fn(?BreedingRecord $record): string =>
                                $record?->inbreeding_status_label ?? 'Clear')
                            ->columnSpan(3),
                        Forms\Components\Placeholder::make('relationship_notes_display')
                            ->label('Relationship Check')
                            ->content(fn(?BreedingRecord $record): string =>
                                $record?->relationship_notes ?: 'No close relationship detected.')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Pregnancy & Delivery')
                    ->description('Update pregnancy diagnosis, delivery outcome, and birth details.')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('pregnancy_status')
                            ->label('Pregnancy Status')
                            ->required()
                            ->native(false)
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed Pregnant',
                                'not_pregnant' => 'Not Pregnant',
                                'delivered' => 'Delivered',
                                'aborted' => 'Aborted',
                            ])
                            ->columnSpan(4),
                        Forms\Components\DatePicker::make('pregnancy_checked_at')
                            ->label('Pregnancy Checked At')
                            ->native(false)
                            ->columnSpan(4),
                        Forms\Components\DatePicker::make('delivery_date')
                            ->label('Delivery Date')
                            ->native(false)
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('offspring_count')
                            ->label('Offspring Count')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(4),
                        Forms\Components\Textarea::make('delivery_notes')
                            ->label('Delivery Notes')
                            ->rows(3)
                            ->columnSpan(8),
                        Forms\Components\Textarea::make('notes')
                            ->label('General Notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('expected_due_date')
            ->columns([
                Tables\Columns\TextColumn::make('batch.batch_number')
                    ->label('Batch')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\TextColumn::make('female.tag_number')
                    ->label('Female')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('femaleBreed.breed_name')
                    ->label('Female Breed')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('male.tag_number')
                    ->label('Male')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user-circle'),
                Tables\Columns\TextColumn::make('maleBreed.breed_name')
                    ->label('Male Breed')
                    ->badge()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_cross_breed')
                    ->label('Cross')
                    ->boolean(),
                Tables\Columns\TextColumn::make('mating_date')
                    ->label('Mating')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expected_due_date')
                    ->label('Expected Due')
                    ->date('d M Y')
                    ->sortable()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('gestation_days')
                    ->label('Days')
                    ->badge(),
                Tables\Columns\TextColumn::make('inbreeding_status')
                    ->label('Inbreeding')
                    ->badge()
                    ->color(fn($state): string => match ($state) {
                        'clear' => 'success',
                        'warning' => 'warning',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('pregnancy_status_label')
                    ->label('Pregnancy')
                    ->badge()
                    ->color(fn($record): string => match ($record->pregnancy_status) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'not_pregnant' => 'gray',
                        'delivered' => 'info',
                        'aborted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->label('Delivered')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('offspring_count')
                    ->label('Offspring')
                    ->badge()
                    ->placeholder('0')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch')
                    ->label('Batch')
                    ->relationship('batch', 'batch_number'),
                Tables\Filters\SelectFilter::make('pregnancy_status')
                    ->label('Pregnancy Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed Pregnant',
                        'not_pregnant' => 'Not Pregnant',
                        'delivered' => 'Delivered',
                        'aborted' => 'Aborted',
                    ]),
                Tables\Filters\TernaryFilter::make('is_cross_breed')
                    ->label('Cross Breeding'),
            ])
            ->actions([
                Tables\Actions\Action::make('pregnancyCheck')
                    ->label('Pregnancy Check')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->fillForm(fn(BreedingRecord $record): array => [
                        'pregnancy_status' => $record->pregnancy_status === 'pending'
                            ? 'confirmed'
                            : $record->pregnancy_status,
                        'pregnancy_checked_at' => now('Africa/Nairobi')->toDateString(),
                        'notes' => null,
                    ])
                    ->form([
                        Forms\Components\Select::make('pregnancy_status')
                            ->label('Pregnancy Result')
                            ->required()
                            ->native(false)
                            ->options([
                                'confirmed' => 'Confirmed Pregnant',
                                'not_pregnant' => 'Not Pregnant',
                                'aborted' => 'Aborted',
                            ]),
                        Forms\Components\DatePicker::make('pregnancy_checked_at')
                            ->label('Checked Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Check Notes')
                            ->rows(3),
                    ])
                    ->action(function (BreedingRecord $record, array $data): void {
                        app(BreedingDeliveryService::class)->recordPregnancyCheck($record, $data);

                        Notification::make()
                            ->title('Pregnancy check saved')
                            ->body('The breeding record has been updated successfully.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(BreedingRecord $record): bool =>
                        $record->pregnancy_status !== 'delivered' &&
                        static::canEdit($record)),
                Tables\Actions\Action::make('recordDelivery')
                    ->label('Record Delivery')
                    ->icon('heroicon-o-gift')
                    ->color('success')
                    ->modalWidth('7xl')
                    ->fillForm(fn(BreedingRecord $record): array => [
                        'delivery_date' => now('Africa/Nairobi')->toDateString(),
                        'delivery_notes' => null,
                        'offspring' => [
                            [
                                'sex' => 'Female',
                                'breed_id' => $record->female_breed_id,
                                'tag_number' => null,
                                'purpose' => 'Breeding',
                                'notes' => null,
                            ],
                        ],
                    ])
                    ->form([
                        Forms\Components\DatePicker::make('delivery_date')
                            ->label('Delivery Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required(),
                        Forms\Components\Textarea::make('delivery_notes')
                            ->label('Delivery Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('offspring')
                            ->label('Offspring')
                            ->helperText('Leave Tag Number blank to auto-generate it from the selected breed prefix.')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(12)
                            ->schema([
                                Forms\Components\Select::make('sex')
                                    ->label('Sex')
                                    ->required()
                                    ->native(false)
                                    ->options([
                                        'Male' => 'Male',
                                        'Female' => 'Female',
                                    ])
                                    ->columnSpan(2),
                                Forms\Components\Select::make('breed_id')
                                    ->label('Breed')
                                    ->options(fn(): array => Breed::query()
                                        ->orderBy('breed_name')
                                        ->pluck('breed_name', 'id')
                                        ->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('tag_number')
                                    ->label('Tag Number')
                                    ->placeholder('Auto if blank')
                                    ->maxLength(255)
                                    ->columnSpan(3),
                                Forms\Components\Select::make('purpose')
                                    ->label('Purpose')
                                    ->default('Breeding')
                                    ->native(false)
                                    ->options([
                                        'Breeding' => 'Breeding',
                                        'Sale' => 'Sale',
                                        'Dairy' => 'Dairy',
                                        'Production' => 'Production',
                                    ])
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('notes')
                                    ->label('Notes')
                                    ->maxLength(255)
                                    ->columnSpan(2),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->action(function (BreedingRecord $record, array $data): void {
                        $createdAnimals = app(BreedingDeliveryService::class)
                            ->recordDelivery($record, $data);

                        Notification::make()
                            ->title('Delivery recorded')
                            ->body(count($createdAnimals) . ' offspring animal record(s) created successfully.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(BreedingRecord $record): bool =>
                        in_array($record->pregnancy_status, ['pending', 'confirmed'], true) &&
                        static::canEdit($record)),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn(BreedingRecord $record): bool => static::canEdit($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBreedingRecords::route('/'),
            'edit' => Pages\EditBreedingRecord::route('/{record}/edit'),
        ];
    }
}
