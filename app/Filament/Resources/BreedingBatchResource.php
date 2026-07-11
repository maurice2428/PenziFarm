<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BreedingBatchResource\RelationManagers\RecordsRelationManager;
use App\Filament\Resources\BreedingBatchResource\Pages;
use App\Models\Animal;
use App\Models\BreedingBatch;
use App\Services\Breeding\BreedingBatchLifecycleService;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class BreedingBatchResource extends Resource

{
    protected static ?string $model = BreedingBatch::class;

    protected static ?string $navigationGroup = 'Breeding Management';

    protected static ?string $navigationLabel = 'Batches';

    protected static ?string $modelLabel = 'Breeding Batch';

    protected static ?string $pluralModelLabel = 'Breeding Batches';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 1;
     public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view breeding batches') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function getRelations(): array
    {
        return [
            RecordsRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create breeding batches') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit breeding batches') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete breeding batches') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Batch Details')
                    ->description('Create one breeding batch and save multiple female breeding records under it.')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('batch_number')
                            ->label('Batch No.')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('name')
                            ->label('Batch Name')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-tag')
                            ->helperText('Example: May 2026 Dorper breeding batch')
                            ->columnSpan(5),
                        Forms\Components\DatePicker::make('mating_date')
                            ->label('Mating Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->columnSpan(4),
                        Forms\Components\Select::make('breeding_type')
                            ->label('Breeding Type')
                            ->default('natural')
                            ->required()
                            ->live()
                            ->native(false)
                            ->options([
                                'natural' => 'Natural Service',
                                'artificial_insemination' => 'Artificial Insemination',
                                'embryo_transfer' => 'Embryo Transfer',
                            ])
                            ->prefixIcon('heroicon-o-sparkles')
                            ->helperText('Natural service blocks inbreeding automatically.')
                            ->columnSpan(4),
                        Forms\Components\Toggle::make('allow_cross_breeding')
                            ->label('Allow Cross Breeding')
                            ->helperText('OFF: females must be from the selected male breed. ON: females can be from other breeds under the same species.')
                            ->live()
                            ->default(false)
                            ->afterStateUpdated(function (Set $set): void {
                                $set('female_animal_ids', []);
                            })
                            ->columnSpan(4),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->default('recorded')
                            ->required()
                            ->native(false)
                            ->options([
                                'recorded' => 'Recorded',
                                'pregnancy_check' => 'Pregnancy Check',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->prefixIcon('heroicon-o-check-badge')
                            ->columnSpan(4),
                    ]),
                Forms\Components\Section::make('Sire / Male Animal')
                    ->description('Select the male assigned to this batch. Species will be auto-filled from the selected male.')
                    ->icon('heroicon-o-user-circle')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('male_animal_id')
                            ->label('Male / Sire')
                            ->required()
                            ->searchable()
                            ->live()
                            ->preload()
                            ->options(fn(): array => static::maleOptions())
                            ->afterStateUpdated(function (Set $set, ?int $state): void {
                                $set('female_animal_ids', []);

                                $male = Animal::query()
                                    ->with('breed')
                                    ->find($state);

                                if (!$male) {
                                    $set('male_breed_id', null);
                                    $set('species', null);

                                    return;
                                }

                                $species = $male->species
                                    ?: $male->breed?->parent_category;

                                $set('male_breed_id', $male->breed_id);
                                $set('species', $species);
                            })
                            ->prefixIcon('heroicon-o-user-circle')
                            ->helperText('After selecting the male, females will load from the selected male’s breed.')
                            ->columnSpan(8),
                        Forms\Components\Hidden::make('male_breed_id')
                            ->dehydrated(),
                        Forms\Components\TextInput::make('species')
                            ->label('Species')
                            ->readOnly()
                            ->dehydrated()
                            ->prefixIcon('heroicon-o-globe-alt')
                            ->helperText('Auto-filled from the selected male animal.')
                            ->columnSpan(4),
                    ]),
                Forms\Components\Section::make('Select Females')
                    ->description('Females are grouped by breed. By default, only females from the selected male breed are shown.')
                    ->icon('heroicon-o-users')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('female_animal_ids')
                            ->label('Females / Dams')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->dehydrated(false)
                            ->options(fn(Get $get): array => static::femaleGroupedOptions(
                                maleId: $get('male_animal_id') ? (int) $get('male_animal_id') : null,
                                allowCrossBreeding: (bool) $get('allow_cross_breeding'),
                            ))
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('select_all_eligible')
                                    ->label('Select All Eligible')
                                    ->icon('heroicon-o-check-circle')
                                    ->action(function (Get $get, Set $set): void {
                                        $ids = static::eligibleFemaleQuery(
                                            maleId: $get('male_animal_id') ? (int) $get('male_animal_id') : null,
                                            allowCrossBreeding: (bool) $get('allow_cross_breeding'),
                                        )
                                            ->pluck('id')
                                            ->map(fn($id) => (string) $id)
                                            ->toArray();

                                        $set('female_animal_ids', $ids);
                                    })
                            )
                            ->helperText('Natural breeding will still block close relatives when saving the batch.')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Batch Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('mating_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Batch No.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Batch Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-tag'),
                Tables\Columns\TextColumn::make('male.tag_number')
                    ->label('Sire')
                    ->searchable()
                    ->icon('heroicon-o-user-circle'),
                Tables\Columns\TextColumn::make('maleBreed.breed_name')
                    ->label('Sire Breed')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('species')
                    ->label('Species')
                    ->badge()
                    ->icon('heroicon-o-globe-alt'),
                Tables\Columns\TextColumn::make('breeding_type_label')
                    ->label('Type')
                    ->badge()
                    ->color(fn($record): string => match ($record->breeding_type) {
                        'natural' => 'success',
                        'artificial_insemination' => 'info',
                        'embryo_transfer' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('allow_cross_breeding')
                    ->label('Cross')
                    ->boolean(),
                Tables\Columns\TextColumn::make('total_females')
                    ->label('Females')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('mating_date')
                    ->label('Mating Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                Tables\Columns\TextColumn::make('expected_due_from')
                    ->label('Due From')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('expected_due_to')
                    ->label('Due To')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record): string => match ($record->status) {
                        'recorded' => 'info',
                        'pregnancy_check' => 'warning',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Archived At')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('archive_reason')
                    ->label('Archive Reason')
                    ->limit(45)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Archived batches'),
            ])
            ->actions([
                Tables\Actions\Action::make('printBatch')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn(BreedingBatch $record): string => route('breeding.batches.print', [
                        'ids' => $record->id,
                    ]))
                    ->openUrlInNewTab()
                    ->visible(fn(BreedingBatch $record): bool =>
                        ! $record->trashed()
                        && (
                            auth()->user()?->can('print breeding batches')
                            || auth()->user()?->can('view breeding batches')
                            || auth()->user()?->hasRole('Admin')
                            || auth()->user()?->hasRole('Administrator')
                        )),
                /*Tables\Actions\Action::make('records')
                    ->label('Records')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->url(fn(BreedingBatch $record): string => BreedingRecordResource::getUrl('index', [
                        'tableFilters[batch][value]' => $record->id,
                    ])),*/
               /* Tables\Actions\Action::make('openBatch')
                    ->label('Open Batch')
                    ->icon('heroicon-o-folder-open')
                    ->color('info')
                    ->url(fn(BreedingBatch $record): string => static::getUrl('edit', [
                        'record' => $record,
                    ])),*/
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (BreedingBatch $record): bool =>
                            ! $record->trashed()
                    ),

                Tables\Actions\Action::make('manageLifecycle')
                    ->label('Archive / Delete')
                    ->icon('heroicon-o-archive-box')
                    ->color('danger')
                    ->visible(
                        fn (BreedingBatch $record): bool =>
                            ! $record->trashed()
                            && static::canDelete($record)
                    )
                    ->modalWidth('2xl')
                    ->modalHeading(
                        fn (BreedingBatch $record): string =>
                            'Archive or permanently delete '
                            . $record->batch_number
                    )
                    ->modalDescription(
                        fn (BreedingBatch $record): HtmlString =>
                            static::lifecycleSummaryHtml($record)
                    )
                    ->form([
                        Forms\Components\Radio::make('disposition')
                            ->label('What should happen to this batch?')
                            ->options([
                                'archive' =>
                                    'Archive batch and all breeding outcomes',
                                'permanent_delete' =>
                                    'Permanently delete this empty batch',
                            ])
                            ->descriptions([
                                'archive' =>
                                    'Recommended. Removes the batch and all '
                                    . 'its animals from Breeding Outcomes, '
                                    . 'but preserves history for restoration.',
                                'permanent_delete' =>
                                    'Only allowed when the batch has no '
                                    . 'completed delivery or registered '
                                    . 'offspring. This cannot be undone.',
                            ])
                            ->default('archive')
                            ->live()
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Archive reason')
                            ->rows(3)
                            ->maxLength(1000)
                            ->required(
                                fn (Get $get): bool =>
                                    $get('disposition') === 'archive'
                            )
                            ->visible(
                                fn (Get $get): bool =>
                                    $get('disposition') === 'archive'
                            ),
                    ])
                    ->modalSubmitActionLabel('Continue')
                    ->action(function (
                        BreedingBatch $record,
                        array $data
                    ): void {
                        $service = app(
                            BreedingBatchLifecycleService::class
                        );

                        if (
                            ($data['disposition'] ?? 'archive')
                            === 'permanent_delete'
                        ) {
                            $service->permanentlyDelete($record);

                            Notification::make()
                                ->title('Breeding batch permanently deleted')
                                ->body(
                                    'The empty batch and all of its '
                                    . 'breeding outcome rows were removed.'
                                )
                                ->success()
                                ->send();

                            return;
                        }

                        $service->archive(
                            $record,
                            $data['reason'] ?? null
                        );

                        Notification::make()
                            ->title('Breeding batch archived')
                            ->body(
                                'The batch and all associated breeding '
                                . 'outcomes are now hidden. They can be '
                                . 'restored from Archived batches.'
                            )
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('restoreBatch')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Restore breeding batch')
                    ->modalDescription(
                        'This restores the batch and every breeding '
                        . 'outcome archived with it.'
                    )
                    ->visible(
                        fn (BreedingBatch $record): bool =>
                            $record->trashed()
                            && static::canDelete($record)
                    )
                    ->action(function (
                        BreedingBatch $record
                    ): void {
                        app(BreedingBatchLifecycleService::class)
                            ->restore($record);

                        Notification::make()
                            ->title('Breeding batch restored')
                            ->body(
                                'All associated breeding outcomes are '
                                . 'visible again.'
                            )
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make(
                    'permanentlyDeleteArchived'
                )
                    ->label('Delete permanently')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Permanently delete archived batch'
                    )
                    ->modalDescription(
                        fn (BreedingBatch $record): HtmlString =>
                            static::lifecycleSummaryHtml($record)
                    )
                    ->modalSubmitActionLabel('Delete permanently')
                    ->visible(
                        fn (BreedingBatch $record): bool =>
                            $record->trashed()
                            && static::canDelete($record)
                    )
                    ->action(function (
                        BreedingBatch $record
                    ): void {
                        app(BreedingBatchLifecycleService::class)
                            ->permanentlyDelete($record);

                        Notification::make()
                            ->title('Archived batch permanently deleted')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelectedBatches')
                        ->label('Print Selected Batches')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Print selected breeding batches')
                        ->modalDescription('This will generate one smart PDF report for the selected breeding batches, including due dates, female records, inbreeding checks, cross-breeding flags, and insights.')
                        ->modalSubmitActionLabel('Generate PDF')
                        ->action(function (EloquentCollection $records) {
                            $ids = $records
                                ->pluck('id')
                                ->filter()
                                ->implode(',');

                            if (blank($ids)) {
                                Notification::make()
                                    ->title('No breeding batches selected')
                                    ->body('Please select at least one breeding batch before printing.')
                                    ->warning()
                                    ->send();

                                return null;
                            }

                            return redirect()->to(route('breeding.batches.print', [
                                'ids' => $ids,
                            ]));
                        })
                        ->visible(fn(): bool =>
                            auth()->user()?->can('print breeding batches') ||
                            auth()->user()?->can('view breeding batches') ||
                            auth()->user()?->hasRole('Admin') ||
                            auth()->user()?->hasRole('Administrator')),
                ]),
            ]);
    }

    public static function lifecycleSummaryHtml(
        BreedingBatch $record
    ): HtmlString {
        $summary = app(
            BreedingBatchLifecycleService::class
        )->summary($record);

        $permanentMessage = $summary['can_permanently_delete']
            ? '<span style="color:#15803d;font-weight:700">'
                . 'Permanent deletion is currently allowed.'
                . '</span>'
            : '<span style="color:#b91c1c;font-weight:700">'
                . 'Permanent deletion is blocked. Archive this batch.'
                . '</span>';

        return new HtmlString(
            '<div style="display:grid;gap:10px">'
            . '<div style="padding:12px;border:1px solid #d1d5db;'
            . 'border-left:5px solid #14532d;background:#f8fafc">'
            . '<strong>' . e($record->name) . '</strong><br>'
            . '<span style="color:#64748b">'
            . e($record->batch_number) . '</span></div>'
            . '<div style="display:grid;grid-template-columns:'
            . 'repeat(3,minmax(0,1fr));gap:8px">'
            . static::summaryBox(
                'Breeding outcomes',
                $summary['records']
            )
            . static::summaryBox(
                'Delivered',
                $summary['delivered_records']
            )
            . static::summaryBox(
                'Registered offspring',
                $summary['registered_offspring']
            )
            . static::summaryBox(
                'Abortions',
                $summary['aborted_records']
            )
            . static::summaryBox(
                'Live births',
                $summary['live_births']
            )
            . static::summaryBox(
                'Stillborn',
                $summary['stillborn']
            )
            . '</div>'
            . '<div style="padding:10px;border:1px solid #e5e7eb;'
            . 'background:#fff">' . $permanentMessage . '</div>'
            . '</div>'
        );
    }

    private static function summaryBox(
        string $label,
        int $value
    ): string {
        return '<div style="padding:9px;border:1px solid #e5e7eb;'
            . 'background:#fff">'
            . '<div style="font-size:10px;color:#64748b;'
            . 'text-transform:uppercase">' . e($label) . '</div>'
            . '<div style="font-size:18px;font-weight:800;color:#111827">'
            . number_format($value)
            . '</div></div>';
    }

    public static function maleOptions(): array
    {
        return Animal::query()
            ->with('breed')
            ->where('sex', 'Male')
            ->where('status', 'Active')
            ->where(function (Builder $query): void {
                $query
                    ->where('is_archived', false)
                    ->orWhereNull('is_archived');
            })
            ->orderBy('tag_number')
            ->get()
            ->mapWithKeys(fn(Animal $animal): array => [
                $animal->id => static::animalLabel($animal),
            ])
            ->toArray();
    }

    public static function eligibleFemaleQuery(?int $maleId, bool $allowCrossBreeding): Builder
    {
        $male = Animal::query()
            ->with('breed')
            ->find($maleId);

        $query = Animal::query()
            ->with('breed')
            ->where('sex', 'Female')
            ->where('status', 'Active')
            ->where(function (Builder $query): void {
                $query
                    ->where('is_archived', false)
                    ->orWhereNull('is_archived');
            });

        if (!$male) {
            return $query->whereRaw('1 = 0');
        }

        $maleSpecies = $male->species
            ?: $male->breed?->parent_category;

        if ($maleSpecies) {
            $query->where(function (Builder $speciesQuery) use ($maleSpecies): void {
                $speciesQuery
                    ->where('species', $maleSpecies)
                    ->orWhereHas('breed', function (Builder $breedQuery) use ($maleSpecies): void {
                        $breedQuery->where('parent_category', $maleSpecies);
                    });
            });
        }

        if (!$allowCrossBreeding) {
            $query->where('breed_id', $male->breed_id);
        }

        return $query->orderBy('tag_number');
    }

    public static function femaleGroupedOptions(?int $maleId, bool $allowCrossBreeding): array
    {
        return static::eligibleFemaleQuery($maleId, $allowCrossBreeding)
            ->get()
            ->groupBy(fn(Animal $animal): string => $animal->breed?->breed_name ?? 'Unknown Breed')
            ->map(fn($animals): array =>
                $animals
                    ->mapWithKeys(fn(Animal $animal): array => [
                        $animal->id => static::animalLabel($animal),
                    ])
                    ->toArray())
            ->toArray();
    }

    public static function animalLabel(Animal $animal): string
    {
        $breed = $animal->breed?->breed_name ?? 'No Breed';

        $species = $animal->species
            ?: $animal->breed?->parent_category
            ?: 'Unknown Species';

        $tag = $animal->tag_number ?? 'No Tag';

        return "{$tag} | {$breed} | {$species}";
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBreedingBatches::route('/'),
            'create' => Pages\CreateBreedingBatch::route('/create'),
            'edit' => Pages\EditBreedingBatch::route('/{record}/edit'),
        ];
    }
}
