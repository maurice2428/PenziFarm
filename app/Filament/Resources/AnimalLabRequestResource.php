<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Livestock\Animals as AnimalsCluster;
use App\Filament\Resources\AnimalLabRequestResource\Pages;
use App\Filament\Support\VeterinaryClinicForm;
use App\Models\Animal;
use App\Models\AnimalClinicalCase;
use App\Models\AnimalLabRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnimalLabRequestResource extends Resource
{
    protected static ?string $model = AnimalLabRequest::class;

    // protected static ?string $cluster = AnimalsCluster::class;

    protected static ?string $navigationGroup = 'Animal Health';

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Lab Requests';

    protected static ?string $modelLabel = 'Lab Request';

    protected static ?string $pluralModelLabel = 'Lab Requests';

    protected static ?int $navigationSort = 31;

    public static function getNavigationBadge(): ?string
    {
        $activeRequests = static::getModel()::query()
            ->whereNotIn('status', ['Completed', 'Cancelled'])
            ->count();

        return $activeRequests > 0 ? (string) $activeRequests : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Active laboratory requests awaiting completion';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return static::allowed('view lab requests');
    }

    public static function canCreate(): bool
    {
        return static::allowed('create lab requests');
    }

    public static function canEdit($record): bool
    {
        return static::allowed('edit lab requests');
    }

    public static function canDelete($record): bool
    {
        return static::allowed('delete lab requests');
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

    public static function specimenOptions(): array
    {
        return [
            'Carcass' => 'Carcass',
            'Whole Blood' => 'Whole Blood',
            'Serum' => 'Serum',
            'Plasma' => 'Plasma',
            'Buffy Coat' => 'Buffy Coat',
            'Faeces' => 'Faeces / Stool Sample',
            'Urine' => 'Urine',
            'Mucus / Nasal Swab' => 'Mucus / Nasal Swab',
            'Saliva' => 'Saliva',
            'Milk' => 'Milk',
            'Hair / Fur / Feathers' => 'Hair / Fur / Feathers',
            'Biopsy Tissue' => 'Biopsy Tissue',
            'Lymph Nodes' => 'Lymph Nodes',
            'Organ Sample' => 'Liver, Kidney or Organ Sample',
            'Vaginal Swab' => 'Vaginal Swab',
            'Amniotic Fluid' => 'Amniotic Fluid',
            'Water Sample' => 'Water Sample',
            'Feed Sample' => 'Feed Sample',
            'Radiographic Study' => 'Radiographic Study',
        ];
    }

    public static function purposeOptions(): array
    {
        return [
            'Diagnosis' => 'Diagnosis',
            'Screening' => 'Screening',
            'Confirmation' => 'Confirmation',
            'Export' => 'Export',
            'Surveillance' => 'Surveillance',
            'Research' => 'Research',
            'Assess for Complications' => 'Assess for Complications',
        ];
    }

    public static function testOptions(): array
    {
        return [
            'Blood Tests' => 'Blood Tests',
            'Microbiology Tests' => 'Microbiology Tests',
            'Virology Tests' => 'Virology Tests',
            'Urinalysis' => 'Urinalysis',
            'Parasitology' => 'Parasitology',
            'Serology' => 'Serology - Antibody / Antigen',
            'Pathology & Cytology' => 'Pathology & Cytology',
            'Toxicology Tests' => 'Toxicology Tests',
            'Milk & Mastitis Testing' => 'Milk & Mastitis Testing',
            'Reproductive & Genetic Tests' => 'Reproductive & Genetic Tests',
            'X-Ray' => 'X-Ray',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Laboratory Request Details')
                ->description(
                    'Link the request to a sick case, affected animal, clinic or laboratory, and current workflow status.'
                )
                ->icon('heroicon-o-beaker')
                ->iconColor('info')
                ->schema([
                    Forms\Components\TextInput::make('request_number')
                        ->label('Request Number')
                        ->prefixIcon('heroicon-o-hashtag')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (string $operation): bool => $operation === 'edit'),

                    Forms\Components\Select::make('clinical_case_id')
                        ->label('Related Sick Case')
                        ->prefixIcon('heroicon-o-face-frown')
                        ->relationship(
                            name: 'clinicalCase',
                            titleAttribute: 'case_number',
                            modifyQueryUsing: fn (Builder $query) => $query
                                ->with('animal')
                                ->latest('case_date')
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (AnimalClinicalCase $case): string =>
                                $case->case_number
                                . ' — '
                                . ($case->animal?->tag_number ?? 'Animal not available')
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->default(
                            fn (): ?int =>
                                request()->filled('clinical_case_id')
                                    ? (int) request()->query('clinical_case_id')
                                    : null
                        )
                        ->live()
                        ->afterStateUpdated(
                            function ($state, Forms\Get $get, Forms\Set $set): void {
                                $case = AnimalClinicalCase::find($state);

                                if (! $case) {
                                    return;
                                }

                                $set('animal_id', $case->animal_id);

                                if (blank($get('clinical_signs'))) {
                                    $set('clinical_signs', $case->clinical_signs);
                                }

                                if (blank($get('length_of_illness'))) {
                                    $set(
                                        'length_of_illness',
                                        $case->length_of_illness
                                    );
                                }

                                if (blank($get('temperature_c'))) {
                                    $set(
                                        'temperature_c',
                                        $case->temperature_c
                                    );
                                }

                                if (blank($get('animal_source'))) {
                                    $set(
                                        'animal_source',
                                        $case->animal_source
                                    );
                                }

                                if (blank($get('attending_officer'))) {
                                    $set(
                                        'attending_officer',
                                        $case->attending_officer
                                    );
                                }
                            }
                        ),

                    Forms\Components\Select::make('animal_id')
                        ->label('Affected Animal')
                        ->prefixIcon('heroicon-o-tag')
                        ->relationship(
                            name: 'animal',
                            titleAttribute: 'tag_number',
                            modifyQueryUsing: fn (Builder $query) => $query
                                ->where('is_archived', false)
                                ->with('breed')
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (Animal $animal): string =>
                                $animal->tag_number
                                . ' — '
                                . ($animal->breed?->breed_name ?? 'Unknown Breed')
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->default(
                            fn (): ?int =>
                                request()->filled('animal_id')
                                    ? (int) request()->query('animal_id')
                                    : null
                        )
                        ->required()
                        ->disabled(
                            fn (Forms\Get $get): bool =>
                                filled($get('clinical_case_id'))
                        )
                        ->dehydrated(),

                    VeterinaryClinicForm::select(),

                    Forms\Components\Hidden::make('clinic_name')
                        ->dehydrated(true),

                    Forms\Components\Select::make('status')
                        ->prefixIcon('heroicon-o-signal')
                        ->options(AnimalLabRequest::statuses())
                        ->default('Requested')
                        ->native(false)
                        ->required(),

                    Forms\Components\DateTimePicker::make('requested_at')
                        ->label('Requested At')
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->default(fn () => now())
                        ->seconds(false)
                        ->required(),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),

            Forms\Components\Section::make('Sample & Test Details')
                ->description(
                    'Record the specimen collected, diagnostic purpose, required tests, and sample-handling timeline.'
                )
                ->icon('heroicon-o-clipboard-document-list')
                ->iconColor('primary')
                ->schema([
                    Forms\Components\CheckboxList::make('specimens')
                        ->label('Specimen Collected')
                        ->options(static::specimenOptions())
                        ->columns(2)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('testing_purpose')
                        ->label('Testing Purpose')
                        ->prefixIcon('heroicon-o-magnifying-glass')
                        ->options(static::purposeOptions())
                        ->native(false)
                        ->required(),

                    Forms\Components\CheckboxList::make('requested_tests')
                        ->label('Tests Requested')
                        ->options(static::testOptions())
                        ->columns(2)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('sample_collected_at')
                        ->label('Sample Collected At')
                        ->prefixIcon('heroicon-o-inbox-arrow-down')
                        ->seconds(false),

                    Forms\Components\DateTimePicker::make('dispatched_at')
                        ->label('Sample Dispatched At')
                        ->prefixIcon('heroicon-o-paper-airplane')
                        ->seconds(false),

                    Forms\Components\DateTimePicker::make('testing_date')
                        ->label('Testing Date')
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->seconds(false),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 3,
                ]),

            Forms\Components\Section::make('Clinical Context')
                ->description(
                    'Provide the clinical information that helps the laboratory interpret the sample and requested tests.'
                )
                ->icon('heroicon-o-heart')
                ->iconColor('danger')
                ->schema([
                    Forms\Components\Textarea::make('clinical_signs')
                        ->label('Clinical Signs / Symptoms')
                        ->placeholder(
                            'Describe visible symptoms, behavioural changes, appetite, discharge, injuries, or other relevant observations.'
                        )
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('length_of_illness')
                        ->label('Length of Illness')
                        ->prefixIcon('heroicon-o-clock')
                        ->placeholder('Example: 3 days'),

                    Forms\Components\TextInput::make('temperature_c')
                        ->label('Temperature')
                        ->prefixIcon('heroicon-o-fire')
                        ->numeric()
                        ->step(0.1)
                        ->suffix('°C')
                        ->helperText('Use the animal’s measured temperature in degrees Celsius.'),

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
                        ->default(fn () => auth()->user()?->name),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),

            Forms\Components\Section::make('Results & Attachments')
                ->description(
                    'Upload the laboratory report, capture received results, and document the recommended action.'
                )
                ->icon('heroicon-o-document-check')
                ->iconColor('success')
                ->visible(fn (string $operation): bool => $operation === 'edit')
                ->schema([
                    Forms\Components\DateTimePicker::make('resulted_at')
                        ->label('Results Received At')
                        ->prefixIcon('heroicon-o-check-circle')
                        ->seconds(false),

                    Forms\Components\FileUpload::make('lab_report_path')
                        ->label('Laboratory Report Attachment')
                        ->disk('public')
                        ->directory('animal-lab-reports')
                        ->visibility('public')
                        ->openable()
                        ->downloadable()
                        ->maxSize(10240)
                        ->helperText('Maximum upload size: 10 MB.'),

                    Forms\Components\Textarea::make('results')
                        ->label('Laboratory Results')
                        ->placeholder(
                            'Record key findings, confirmed conditions, test values, or laboratory interpretation.'
                        )
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('recommended_medication')
                        ->label('Recommended Medication / Action')
                        ->placeholder(
                            'Record the laboratory recommendation, medication direction, dosage guidance, or management action.'
                        )
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Other Notes')
                        ->placeholder(
                            'Add follow-up actions, communication notes, special handling instructions, or other observations.'
                        )
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->striped()
            ->emptyStateIcon('heroicon-o-beaker')
            ->emptyStateHeading('No laboratory requests recorded')
            ->emptyStateDescription(
                'Lab requests created from sick cases or directly for individual animals will appear here.'
            )
            ->columns([
                Tables\Columns\TextColumn::make('request_number')
                    ->label('Request No.')
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
                        fn (AnimalLabRequest $record): string =>
                            $record->animal?->breed?->breed_name ?? 'Unknown Breed'
                    )
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('clinic_display')
                    ->label('Clinic / Laboratory')
                    ->icon('heroicon-o-building-office-2')
                    ->iconColor('info')
                    ->state(
                        fn (AnimalLabRequest $record): string =>
                            $record->clinic_display_name
                    )
                    ->limit(35)
                    ->tooltip(
                        fn (AnimalLabRequest $record): string =>
                            $record->clinic_display_name
                    )
                    ->wrap(),

                Tables\Columns\TextColumn::make('requested_tests_text')
                    ->label('Tests')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->iconColor('gray')
                    ->limit(45)
                    ->tooltip(
                        fn (AnimalLabRequest $record): string =>
                            $record->requested_tests_text
                    )
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'Completed' => 'heroicon-o-check-circle',
                        'Cancelled' => 'heroicon-o-x-circle',
                        'In Progress' => 'heroicon-o-arrow-path',
                        'Dispatched' => 'heroicon-o-paper-airplane',
                        'Requested' => 'heroicon-o-clock',
                        default => 'heroicon-o-information-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        'In Progress' => 'warning',
                        'Dispatched' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Requested')
                    ->icon('heroicon-o-calendar-days')
                    ->iconColor('gray')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Request Status')
                    ->options(AnimalLabRequest::statuses()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected Lab Requests')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected Laboratory Requests?')
                        ->modalDescription(
                            'Selected laboratory request records and their uploaded report references will be removed.'
                        )
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('openLabRequestPdf')
                    ->icon('heroicon-m-document-arrow-down')
                    ->iconButton()
                    ->tooltip('Generate laboratory request PDF form')
                    ->color('danger')
                    ->action(function (AnimalLabRequest $record) {
                        return redirect()->route(
                            'animal-lab-requests.pdf',
                            ['labRequest' => $record->getKey()]
                        );
                    }),

                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Edit this laboratory request'),

                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->tooltip('Delete this laboratory request'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnimalLabRequests::route('/'),
            'create' => Pages\CreateAnimalLabRequest::route('/create'),
            'edit' => Pages\EditAnimalLabRequest::route('/{record}/edit'),
        ];
    }
}
