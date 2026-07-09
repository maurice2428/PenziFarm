<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AnimalClinicalCaseResource;
use App\Filament\Resources\AnimalResource;
use App\Models\Animal;
use App\Models\AnimalClinicalCase;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AnimalsUnderObservation extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationLabel = 'Observation';

    protected static ?string $navigationGroup = 'Livestock';

    protected static ?int $navigationSort = 36;

    protected static ?string $title = 'Animals Under Observation';

    protected static ?string $slug = 'animals-under-observation';

    protected static string $view = 'filament.pages.animals-under-observation';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view animals') ?? false;
    }

    public function getViewData(): array
    {
        $animalQuery = Animal::query()
            ->where('is_archived', false)
            ->whereHas('clinicalCases', function (Builder $query): Builder {
                return $query->whereNotIn('status', [
                    'Resolved',
                    'Closed',
                ]);
            });

        return [
            'observationSummary' => [
                'animals' => (clone $animalQuery)->count(),

                'openAnimals' => (clone $animalQuery)
                    ->whereHas(
                        'clinicalCases',
                        fn (Builder $query): Builder => $query->where('status', 'Open')
                    )
                    ->count(),

                'treatmentAnimals' => (clone $animalQuery)
                    ->whereHas(
                        'clinicalCases',
                        fn (Builder $query): Builder => $query->where(
                            'status',
                            'Under Treatment'
                        )
                    )
                    ->count(),

                'referredAnimals' => (clone $animalQuery)
                    ->whereHas(
                        'clinicalCases',
                        fn (Builder $query): Builder => $query->where(
                            'status',
                            'Referred'
                        )
                    )
                    ->count(),

                'criticalCases' => AnimalClinicalCase::query()
                    ->whereNotIn('status', [
                        'Resolved',
                        'Closed',
                    ])
                    ->where('severity', 'Critical')
                    ->count(),

                'activeCases' => AnimalClinicalCase::query()
                    ->whereNotIn('status', [
                        'Resolved',
                        'Closed',
                    ])
                    ->count(),
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getObservationQuery())
            ->heading('Clinical Observation Register')
            ->description(
                'Animals shown here have at least one active clinical case. '
                . 'Selection checkboxes appear beside each record for bulk actions.'
            )
            ->columns([
                Tables\Columns\TextColumn::make('tag_number')
                    ->label('Animal Tag')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->description(
                        fn (Animal $record): string =>
                            ($record->sex ?? 'Unknown sex')
                            . ' · '
                            . ($record->species ?? 'Unknown species')
                    ),

                Tables\Columns\TextColumn::make('breed.breed_name')
                    ->label('Breed')
                    ->searchable()
                    ->sortable()
                    ->default('Not recorded'),

                Tables\Columns\TextColumn::make('location.display_name')
                    ->label('Location')
                    ->default('Not recorded')
                    ->toggleable(),

                Tables\Columns\TextColumn::make(
                    'latestActiveClinicalCase.case_number'
                )
                    ->label('Latest Clinical Case')
                    ->searchable()
                    ->weight('bold')
                    ->description(
                        fn (Animal $record): string =>
                            $record->latestActiveClinicalCase?->case_date
                                ? 'Recorded '
                                    . $record->latestActiveClinicalCase
                                        ->case_date
                                        ->format('d M Y, H:i')
                                : 'No date recorded'
                    ),

                Tables\Columns\TextColumn::make(
                    'latestActiveClinicalCase.status'
                )
                    ->label('Observation Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Open' => 'warning',
                        'Under Treatment' => 'danger',
                        'Referred' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make(
                    'latestActiveClinicalCase.severity'
                )
                    ->label('Severity')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Critical' => 'danger',
                        'High' => 'warning',
                        'Moderate' => 'info',
                        'Low' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make(
                    'latestActiveClinicalCase.clinical_signs'
                )
                    ->label('Clinical Signs')
                    ->limit(55)
                    ->wrap()
                    ->tooltip(
                        fn (Animal $record): ?string =>
                            $record->latestActiveClinicalCase?->clinical_signs
                    ),

                Tables\Columns\TextColumn::make('observation_since')
                    ->label('Observation Since')
                    ->dateTime('d M Y, H:i')
                    ->description(function (?string $state): ?string {
                        if (blank($state)) {
                            return null;
                        }

                        return Carbon::parse($state)->diffForHumans(
                            now(),
                            [
                                'parts' => 2,
                                'syntax' => Carbon::DIFF_ABSOLUTE,
                            ]
                        );
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('active_case_count')
                    ->label('Active Cases')
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('case_status')
                    ->label('Clinical Status')
                    ->options([
                        'Open' => 'Open',
                        'Under Treatment' => 'Under Treatment',
                        'Referred' => 'Referred',
                    ])
                    ->query(function (
                        Builder $query,
                        array $data
                    ): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (
                                Builder $query,
                                string $status
                            ): Builder => $query->whereHas(
                                'activeClinicalCases',
                                fn (Builder $cases): Builder => $cases
                                    ->where('status', $status)
                            )
                        );
                    }),

                Tables\Filters\SelectFilter::make('severity')
                    ->label('Severity')
                    ->options([
                        'Low' => 'Low',
                        'Moderate' => 'Moderate',
                        'High' => 'High',
                        'Critical' => 'Critical',
                    ])
                    ->query(function (
                        Builder $query,
                        array $data
                    ): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (
                                Builder $query,
                                string $severity
                            ): Builder => $query->whereHas(
                                'activeClinicalCases',
                                fn (Builder $cases): Builder => $cases
                                    ->where('severity', $severity)
                            )
                        );
                    }),

                Tables\Filters\SelectFilter::make('breed_id')
                    ->label('Breed')
                    ->relationship('breed', 'breed_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('current_location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_breeder')
                    ->label('Breeding Animals'),
            ])
            ->actions([
                Tables\Actions\Action::make('viewAnimalProfile')
                    ->icon('heroicon-o-identification')
                    ->iconButton()
                    ->tooltip('View animal profile')
                    ->color('primary')
                    ->url(
                        fn (Animal $record): string => AnimalResource::getUrl(
                            'profile',
                            ['record' => $record]
                        )
                    ),

                Tables\Actions\Action::make('openClinicalCase')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->iconButton()
                    ->tooltip('Open latest clinical case')
                    ->color('warning')
                    ->visible(
                        fn (Animal $record): bool =>
                            filled($record->latestActiveClinicalCase)
                    )
                    ->url(
                        fn (Animal $record): ?string =>
                            $record->latestActiveClinicalCase
                                ? AnimalClinicalCaseResource::getUrl(
                                    'edit',
                                    [
                                        'record' => $record
                                            ->latestActiveClinicalCase,
                                    ]
                                )
                                : null
                    ),

                Tables\Actions\Action::make('generateProfilePdf')
                    ->icon('heroicon-o-document-arrow-down')
                    ->iconButton()
                    ->tooltip('Generate animal profile PDF')
                    ->color('danger')
                    ->action(
                        fn (Animal $record) => redirect()->route(
                            'animals.profile.pdf',
                            ['animal' => $record->getKey()]
                        )
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('exportSelectedCsv')
                        ->label('Export Selected CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $records->loadMissing([
                                'breed',
                                'location',
                                'latestActiveClinicalCase',
                            ]);

                            return response()->streamDownload(
                                function () use ($records): void {
                                    $output = fopen('php://output', 'w');

                                    fputcsv($output, [
                                        'Animal Tag',
                                        'Breed',
                                        'Species',
                                        'Sex',
                                        'Location',
                                        'Clinical Case',
                                        'Status',
                                        'Severity',
                                        'Clinical Signs',
                                        'Observation Since',
                                        'Active Case Count',
                                    ]);

                                    foreach ($records as $animal) {
                                        $case = $animal->latestActiveClinicalCase;

                                        fputcsv($output, [
                                            $animal->tag_number,
                                            $animal->breed?->breed_name,
                                            $animal->species,
                                            $animal->sex,
                                            $animal->location?->display_name
                                                ?? $animal->location?->name,
                                            $case?->case_number,
                                            $case?->status,
                                            $case?->severity,
                                            $case?->clinical_signs,
                                            $case?->case_date?->format(
                                                'Y-m-d H:i:s'
                                            ),
                                            $animal->active_case_count,
                                        ]);
                                    }

                                    fclose($output);
                                },
                                'animals-under-observation-'
                                    . now('Africa/Nairobi')
                                        ->format('Ymd_His')
                                    . '.csv',
                                [
                                    'Content-Type' =>
                                        'text/csv; charset=UTF-8',
                                ]
                            );
                        }),

                    Tables\Actions\BulkAction::make(
                        'resolveLatestActiveCases'
                    )
                        ->label('Resolve Latest Active Case')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(
                            'Resolve latest active cases for selected animals?'
                        )
                        ->modalDescription(
                            'For every selected animal, only its newest active clinical case will be marked Resolved. '
                            . 'Use this only after the attending officer has confirmed recovery or closure.'
                        )
                        ->modalSubmitActionLabel(
                            'Resolve selected cases'
                        )
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->loadMissing(
                                'latestActiveClinicalCase'
                            );

                            $resolved = 0;
                            $skipped = 0;

                            foreach ($records as $animal) {
                                $clinicalCase = $animal
                                    ->latestActiveClinicalCase;

                                if (! $clinicalCase) {
                                    $skipped++;

                                    continue;
                                }

                                $clinicalCase->update([
                                    'status' => 'Resolved',
                                    'resolved_at' => now(),
                                ]);

                                $resolved++;
                            }

                            Notification::make()
                                ->success()
                                ->title('Clinical case update completed')
                                ->body(
                                    "{$resolved} latest active case(s) were resolved."
                                    . ($skipped
                                        ? " {$skipped} selected animal(s) had no active case."
                                        : '')
                                )
                                ->send();
                        }),
                ])->label('Selected Animals'),
            ])
            ->emptyStateIcon('heroicon-o-eye-slash')
            ->emptyStateHeading('No animals are currently under observation')
            ->emptyStateDescription(
                'Animals appear automatically when a clinical case is Open, Under Treatment, or Referred.'
            )
            ->defaultPaginationPageOption(25)
            ->defaultSort('observation_since', 'desc');
    }

    private function getObservationQuery(): Builder
    {
        return Animal::query()
            ->select('animals.*')
            ->where('animals.is_archived', false)
            ->with([
                'breed',
                'location',
                'latestActiveClinicalCase',
            ])
            ->withCount([
                'activeClinicalCases as active_case_count',
            ])
            ->whereHas('activeClinicalCases')
            ->selectSub(
                AnimalClinicalCase::query()
                    ->select('case_date')
                    ->whereColumn(
                        'animal_clinical_cases.animal_id',
                        'animals.id'
                    )
                    ->whereNotIn('status', [
                        'Resolved',
                        'Closed',
                    ])
                    ->latest('case_date')
                    ->limit(1),
                'observation_since'
            );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refreshList')
                ->label('Refresh List')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(fn () => $this->resetTable()),
        ];
    }
}
