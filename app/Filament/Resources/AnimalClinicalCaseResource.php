<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Livestock\Animals as AnimalsCluster;
use App\Filament\Resources\AnimalClinicalCaseResource\Pages;
use App\Filament\Resources\AnimalClinicalCaseResource\RelationManagers;
use App\Models\Animal;
use App\Models\AnimalClinicalCase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnimalClinicalCaseResource extends Resource
{
    protected static ?string $model = AnimalClinicalCase::class;

    // protected static ?string $cluster = AnimalsCluster::class;

    protected static ?string $navigationGroup = 'Animal Health';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationIcon = 'heroicon-o-face-frown';

    protected static ?string $navigationLabel = 'Sick Cases';

    protected static ?string $modelLabel = 'Sick Case';

    protected static ?string $pluralModelLabel = 'Sick Cases';

    public static function getNavigationBadge(): ?string
    {
        $openCases = static::getModel()::query()
            ->whereIn('status', [
                'Open',
                'Under Treatment',
                'Referred',
            ])
            ->count();

        return $openCases > 0 ? (string) $openCases : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Active sick cases requiring follow-up';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return static::allowed('view clinical cases');
    }

    public static function canCreate(): bool
    {
        return static::allowed('create clinical cases');
    }

    public static function canEdit($record): bool
    {
        return static::allowed('edit clinical cases');
    }

    public static function canDelete($record): bool
    {
        return static::allowed('delete clinical cases');
    }

    private static function allowed(string $permission): bool
    {
        $user = auth()->user();

        return ($user?->can($permission) ?? false) ||
            ($user?->hasAnyRole([
                'Administrator',
                'Admin',
                'Manager',
                'Veterinary Officer',
                'Vet',
            ]) ?? false);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Affected Animal & Case Details')
                ->description('Record the affected animal, urgency, source, and attending veterinary officer.')
                ->icon('heroicon-o-identification')
                ->iconColor('primary')
                ->schema([
                    Forms\Components\Select::make('animal_id')
                        ->label('Affected Animal')
                        ->prefixIcon('heroicon-o-tag')
                        ->relationship(
                            'animal',
                            'tag_number',
                            fn (Builder $query) => $query
                                ->where('is_archived', false)
                                ->with('breed')
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (Animal $animal): string =>
                                $animal->tag_number
                                . ' - '
                                . ($animal->breed?->breed_name ?? 'Unknown Breed')
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required(),

                    Forms\Components\DateTimePicker::make('case_date')
                        ->label('Case Date & Time')
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->default(fn () => now())
                        ->seconds(false)
                        ->required(),

                    Forms\Components\TextInput::make('case_number')
                        ->label('Case Number')
                        ->prefixIcon('heroicon-o-hashtag')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (string $operation): bool => $operation === 'edit'),

                    Forms\Components\Select::make('severity')
                        ->prefixIcon('heroicon-o-exclamation-triangle')
                        ->options(AnimalClinicalCase::severities())
                        ->default('Moderate')
                        ->native(false)
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->prefixIcon('heroicon-o-signal')
                        ->options(AnimalClinicalCase::statuses())
                        ->default('Open')
                        ->native(false)
                        ->required()
                        ->live(),

                    Forms\Components\TextInput::make('length_of_illness')
                        ->label('Length of Illness')
                        ->prefixIcon('heroicon-o-clock')
                        ->placeholder('Example: 3 days'),

                    Forms\Components\TextInput::make('temperature_c')
                        ->label('Temperature')
                        ->prefixIcon('heroicon-o-exclamation-triangle')
                        ->numeric()
                        ->step(0.1)
                        ->suffix('°C'),

                    Forms\Components\Select::make('animal_source')
                        ->label('Animal Source')
                        ->prefixIcon('heroicon-o-map-pin')
                        ->options([
                            'Farm' => 'Farm',
                            'Breeder' => 'Breeder',
                            'Supplier' => 'Supplier',
                            'Other' => 'Other',
                        ])
                        ->native(false),

                    Forms\Components\TextInput::make('attending_officer')
                        ->label('Attending Officer')
                        ->prefixIcon('heroicon-o-user-circle')
                        ->default(fn () => auth()->user()?->name)
                        ->maxLength(255),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),

            Forms\Components\Section::make('Clinical Assessment')
                ->description('Capture observed signs, diagnosis, treatment direction, and the final clinical remarks.')
                ->icon('heroicon-o-clipboard-document-list')
                ->iconColor('danger')
                ->schema([
                    Forms\Components\Textarea::make('clinical_signs')
                        ->label('Signs & Symptoms')
                        ->placeholder('Describe the symptoms observed, behaviour changes, injuries, appetite, posture, discharge, and other signs.')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('diagnosis')
                        ->label('Diagnosis / Working Diagnosis')
                        ->placeholder('Record the confirmed or suspected condition.')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('treatment_plan')
                        ->label('Treatment Plan')
                        ->placeholder('Describe medication, dosage plan, monitoring requirements, isolation instructions, or referral direction.')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('remarks')
                        ->label('Remarks')
                        ->placeholder('Add recovery progress, complications, owner instructions, or any additional notes.')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('resolved_at')
                        ->label('Resolved At')
                        ->prefixIcon('heroicon-o-check-circle')
                        ->seconds(false)
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                in_array(
                                    $get('status'),
                                    ['Resolved', 'Closed'],
                                    true
                                )
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('case_date', 'desc')
            ->striped()
            ->emptyStateIcon('heroicon-o-face-frown')
            ->emptyStateHeading('No sick cases have been recorded')
            ->emptyStateDescription(
                'Clinical cases for animals requiring treatment, monitoring, laboratory testing, or referral will appear here.'
            )
            ->columns([
                Tables\Columns\TextColumn::make('case_number')
                    ->label('Case No.')
                    ->icon('heroicon-o-document-text')
                    ->iconColor('primary')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('animal.tag_number')
                    ->label('Animal')
                    ->icon('heroicon-o-tag')
                    ->iconColor('primary')
                    ->description(
                        fn (AnimalClinicalCase $record): string =>
                            $record->animal?->breed?->breed_name ?? 'Unknown Breed'
                    )
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'Critical' => 'heroicon-o-exclamation-triangle',
                        'High' => 'heroicon-o-arrow-trending-up',
                        'Moderate' => 'heroicon-o-minus-circle',
                        default => 'heroicon-o-information-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Critical' => 'danger',
                        'High' => 'warning',
                        'Moderate' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'Resolved', 'Closed' => 'heroicon-o-check-circle',
                        'Under Treatment' => 'heroicon-o-arrow-path',
                        'Referred' => 'heroicon-o-arrow-top-right-on-square',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Resolved', 'Closed' => 'success',
                        'Under Treatment' => 'warning',
                        'Referred' => 'danger',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('clinical_signs')
                    ->label('Clinical Signs')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->iconColor('gray')
                    ->limit(55)
                    ->tooltip(
                        fn (AnimalClinicalCase $record): ?string =>
                            $record->clinical_signs
                    )
                    ->wrap(),

                Tables\Columns\TextColumn::make('treatments_count')
                    ->label('Treatments')
                    ->icon('heroicon-o-heart')
                    ->iconColor('success')
                    ->counts('treatments')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('lab_requests_count')
                    ->label('Lab Requests')
                    ->icon('heroicon-o-beaker')
                    ->iconColor('info')
                    ->counts('labRequests')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('case_date')
                    ->label('Recorded')
                    ->icon('heroicon-o-calendar-days')
                    ->iconColor('gray')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Case Status')
                    ->options(AnimalClinicalCase::statuses()),

                Tables\Filters\SelectFilter::make('severity')
                    ->label('Severity Level')
                    ->options(AnimalClinicalCase::severities()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected Sick Cases')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected Sick Cases?')
                        ->modalDescription(
                            'This removes selected clinical-case records. Review linked treatment and laboratory records before confirming.'
                        )
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('createLabRequest')
                    ->label('Lab Request')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->tooltip('Create a laboratory request linked to this sick case')
                    ->visible(
                        fn (): bool =>
                            static::allowed('create lab requests')
                    )
                    ->url(
                        fn (AnimalClinicalCase $record): string =>
                            AnimalLabRequestResource::getUrl('create', [
                                'animal_id' => $record->animal_id,
                                'clinical_case_id' => $record->id,
                            ])
                    ),

                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Edit this sick case'),

                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->tooltip('Delete this sick case'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TreatmentsRelationManager::class,
            RelationManagers\LabRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnimalClinicalCases::route('/'),
            'create' => Pages\CreateAnimalClinicalCase::route('/create'),
            'edit' => Pages\EditAnimalClinicalCase::route('/{record}/edit'),
        ];
    }
}
