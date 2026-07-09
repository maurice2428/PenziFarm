<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Livestock\Animals as AnimalsCluster;
use App\Filament\Resources\AnimalGroupResource\Pages;
use App\Models\Animal;
use App\Models\AnimalGroup;
use App\Models\Breed;
use App\Models\Location;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class AnimalGroupResource extends Resource
{
    protected static ?string $model = AnimalGroup::class;

    // protected static ?string $cluster = AnimalsCluster::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Group(s)';

    protected static ?string $navigationGroup = 'Livestock';

    protected static ?int $navigationSort = 38;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view animal groups') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view animal groups') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create animal groups') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit animal groups') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete animal groups') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Group Details')
                ->description('Create manual groups or smart auto-sync groups for active animals.')
                ->icon('heroicon-o-squares-2x2')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                ->schema([
                    Forms\Components\TextInput::make('group_code')
                        ->label('Group Code')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto generated'),
                    Forms\Components\TextInput::make('name')
                        ->label('Group Name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('group_type')
                        ->label('Group Type')
                        ->options([
                            'manual' => 'Manual Group',
                            'dynamic' => 'Dynamic Smart Group',
                            'breeding' => 'Breeding Group',
                            'sales' => 'Sales Group',
                            'health' => 'Health Monitoring Group',
                            'feeding' => 'Feeding Group',
                            'location' => 'Location Group',
                        ])
                        ->default('dynamic')
                        ->required()
                        ->live(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'archived' => 'Archived',
                        ])
                        ->default('active')
                        ->required(),
                    Forms\Components\Toggle::make('auto_sync')
                        ->label('Auto Sync Matching Animals')
                        ->helperText('When enabled, the group automatically adds all active animals matching location, breed, and sex.')
                        ->default(true)
                        ->live(),
                    Forms\Components\TextInput::make('purpose')
                        ->label('Group Purpose / Notes Label')
                        ->placeholder('Example: Breeding batch, feeding batch, show animals')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Smart Auto-Sync Criteria')
                ->description('Only location, breed, and sex are used. Purpose is not used for matching animals.')
                ->icon('heroicon-o-funnel')
                ->columns([
                    'default' => 1,
                    'md' => 3,
                ])
                ->schema([
                    Forms\Components\Select::make('location_id')
                        ->label('Location')
                        ->options(fn(): array => Location::query()
                            ->active()
                            ->defaultFirst()
                            ->pluck('name', 'id')
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set): void {
                            $set('animal_ids', []);
                        })
                        ->helperText('Optional. Leave empty to include all locations.'),
                    Forms\Components\Select::make('breed_id')
                        ->label('Breed')
                        ->options(fn(): array => Breed::query()
                            ->orderBy('parent_category')
                            ->orderBy('breed_name')
                            ->pluck('breed_name', 'id')
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set): void {
                            $set('animal_ids', []);
                        })
                        ->helperText('Optional. Leave empty to include all breeds.'),
                    Forms\Components\Select::make('sex')
                        ->label('Sex')
                        ->options([
                            'Male' => 'Male',
                            'Female' => 'Female',
                        ])
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set): void {
                            $set('animal_ids', []);
                        })
                        ->helperText('Optional. Leave empty to include both male and female.'),
                ]),
            Forms\Components\Section::make('Animal Selection Preview / Manual Members')
                ->description('If Auto Sync is ON, these matching animals will be added automatically after saving. If Auto Sync is OFF, select animals manually.')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\Placeholder::make('matching_animals_preview')
                        ->label('Matching Animals')
                        ->content(function (Forms\Get $get): string {
                            $count = Animal::query()
                                ->where('status', 'Active')
                                ->where('is_archived', false)
                                ->when(
                                    $get('location_id'),
                                    fn($query, $locationId) => $query->where('current_location_id', $locationId)
                                )
                                ->when(
                                    $get('breed_id'),
                                    fn($query, $breedId) => $query->where('breed_id', $breedId)
                                )
                                ->when(
                                    $get('sex'),
                                    fn($query, $sex) => $query->where('sex', $sex)
                                )
                                ->count();

                            return number_format($count) . ' active animal(s) match this group criteria.';
                        })
                        ->columnSpanFull(),
                    Forms\Components\Select::make('animal_ids')
                        ->label(fn(Forms\Get $get): string =>
                            $get('auto_sync')
                                ? 'Auto-Sync Animal Preview'
                                : 'Manual Animal Tags')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->dehydrated(false)
                        ->disabled(fn(Forms\Get $get): bool => (bool) $get('auto_sync'))
                        ->options(function (Forms\Get $get): array {
                            return Animal::query()
                                ->with(['breed', 'location'])
                                ->where('status', 'Active')
                                ->where('is_archived', false)
                                ->when(
                                    $get('location_id'),
                                    fn($query, $locationId) => $query->where('current_location_id', $locationId)
                                )
                                ->when(
                                    $get('breed_id'),
                                    fn($query, $breedId) => $query->where('breed_id', $breedId)
                                )
                                ->when(
                                    $get('sex'),
                                    fn($query, $sex) => $query->where('sex', $sex)
                                )
                                ->orderBy('tag_number')
                                ->get()
                                ->mapWithKeys(function (Animal $animal): array {
                                    $breed = $animal->breed?->breed_name ?: 'No breed';
                                    $location = $animal->location?->name ?: 'No location';

                                    return [
                                        $animal->id => $animal->tag_number . ' — ' . $breed . ' — ' . $location,
                                    ];
                                })
                                ->toArray();
                        })
                        ->helperText(function (Forms\Get $get): string {
                            return $get('auto_sync')
                                ? 'Auto Sync is ON. Matching active animals will be added automatically after saving.'
                                : 'Auto Sync is OFF. Select the animal tags you want to add manually.';
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('group_code')
                    ->label('Code')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Group')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('group_type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\IconColumn::make('auto_sync')
                    ->label('Auto')
                    ->boolean(),
                Tables\Columns\TextColumn::make('active_members_count')
                    ->label('Animals')
                    ->counts('activeMembers')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->default('All locations')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('breed.breed_name')
                    ->label('Breed')
                    ->default('All breeds')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sex')
                    ->label('Sex')
                    ->default('All')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('sync')
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn(AnimalGroup $record): bool =>
                        $record->auto_sync &&
                        (auth()->user()?->can('sync animal groups') ?? false))
                    ->action(function (AnimalGroup $record): void {
                        $count = $record->syncDynamicMembers();

                        Notification::make()
                            ->success()
                            ->title('Animal group synced')
                            ->body(number_format($count) . ' active animal(s) now match this group.')
                            ->send();
                    }),
                Tables\Actions\Action::make('print')
                    ->label('PDF')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->visible(fn(): bool => auth()->user()?->can('print animal group reports') ?? false)
                    ->action(function (AnimalGroup $record) {
                        $record->load([
                            'location',
                            'breed',
                            'activeMembers.animal.breed',
                            'activeMembers.animal.location',
                        ]);

                        $pdf = Pdf::loadView('pdf.animal-group-report', [
                            'group' => $record,
                            'generatedBy' => auth()->user(),
                            'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
                        ])
                            ->setPaper('a4', 'landscape')
                            ->setOptions([
                                'isHtml5ParserEnabled' => true,
                                'isRemoteEnabled' => false,
                                'dpi' => 96,
                                'defaultFont' => 'Courier',
                                'enable_php' => true,
                            ]);

                        return response()->streamDownload(
                            fn() => print ($pdf->output()),
                            $record->group_code . '.pdf'
                        );
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(): bool => auth()->user()?->can('delete animal groups') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => auth()->user()?->can('delete animal groups') ?? false),
                ]),
            ]);
    }

    public static function syncGroupAnimals(AnimalGroup $group, array $animalIds): void
    {
        $group->animal_status = null;
        $group->animal_purpose = null;
        $group->saveQuietly();

        if ($group->auto_sync) {
            $count = $group->syncDynamicMembers();

            Notification::make()
                ->success()
                ->title('Animal group synced')
                ->body(number_format($count) . ' active animal(s) were added using location, breed, and sex criteria.')
                ->send();

            return;
        }

        $animalIds = collect($animalIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $group
            ->members()
            ->whereNotIn('animal_id', $animalIds)
            ->where('status', 'active')
            ->update([
                'status' => 'removed',
                'left_at' => now(),
            ]);

        foreach ($animalIds as $animalId) {
            $group->members()->updateOrCreate(
                [
                    'animal_id' => $animalId,
                ],
                [
                    'status' => 'active',
                    'joined_at' => now(),
                    'left_at' => null,
                    'created_by' => auth()->id(),
                ]
            );
        }

        Notification::make()
            ->success()
            ->title('Manual animal group updated')
            ->body(number_format($animalIds->count()) . ' selected animal(s) are active in this group.')
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnimalGroups::route('/'),
            'create' => Pages\CreateAnimalGroup::route('/create'),
            'edit' => Pages\EditAnimalGroup::route('/{record}/edit'),
        ];
    }
}
