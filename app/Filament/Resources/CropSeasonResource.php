<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropSeasonResource\Pages;
use App\Models\CropCareTask;
use App\Models\CropCatalog;
use App\Models\CropSeason;
use App\Models\FarmField;
use App\Models\FieldPartition;
use App\Services\Crops\CropCalendarService;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class CropSeasonResource extends Resource
{
    protected static ?string $model = CropSeason::class;

    protected static ?string $navigationGroup = 'Crop Farming';

    protected static ?string $navigationLabel = 'Season(s)';

    protected static ?string $modelLabel = 'Crop Season';

    protected static ?string $pluralModelLabel = 'Crop Seasons';

    protected static ?string $navigationIcon = 'heroicon-o-sun';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'crop-farming/seasons';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view crop seasons') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create crop seasons') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit crop seasons') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete crop seasons') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Season Identity')
                    ->description('Create crop production cycles for maize, avocados, fodder, vegetables, orchards, and field crops.')
                    ->icon('heroicon-o-sun')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('season_code')
                            ->label('Season Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('name')
                            ->label('Season Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Dhiwa Maize May 2026')
                            ->prefixIcon('heroicon-o-sun')
                            ->columnSpan(5),
                        Forms\Components\Select::make('crop_catalog_id')
                            ->label('Crop')
                            ->options(fn(): array => CropCatalog::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn(CropCatalog $crop): array => [
                                    $crop->id => $crop->display_name . ' • ' . $crop->category_label,
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(?int $state, Get $get, Set $set) =>
                                static::applyCropCalendar($state, $get('planting_date') ?: $get('start_date'), $set))
                            ->prefixIcon('heroicon-o-sparkles')
                            ->columnSpan(4),
                        Forms\Components\Select::make('farm_field_id')
                            ->label('Field')
                            ->options(fn(): array => FarmField::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('field_partition_id', null);
                            })
                            ->prefixIcon('heroicon-o-map')
                            ->columnSpan(4),
                        Forms\Components\Select::make('field_partition_id')
                            ->label('Partition / Block')
                            ->options(fn(Get $get): array => FieldPartition::query()
                                ->when($get('farm_field_id'), fn($query, $fieldId) => $query->where('farm_field_id', $fieldId))
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn(FieldPartition $partition): array => [
                                    $partition->id => $partition->name . ' • ' . number_format((float) $partition->area, 3) . ' ' . $partition->area_unit . ' • ' . $partition->status_label,
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-squares-2x2')
                            ->columnSpan(4),
                        Forms\Components\Select::make('planting_type')
                            ->label('Planting Type')
                            ->native(false)
                            ->default('direct_seed')
                            ->required()
                            ->options([
                                'direct_seed' => 'Direct Seed',
                                'transplant' => 'Transplant',
                                'nursery_transfer' => 'Nursery Transfer',
                                'orchard' => 'Orchard',
                            ])
                            ->columnSpan(4),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->live()
                            ->columnSpan(4),
                        Forms\Components\DatePicker::make('planting_date')
                            ->label('Planting Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn($state, Get $get, Set $set) =>
                                static::applyCropCalendar($get('crop_catalog_id'), $state, $set))
                            ->columnSpan(4),
                    ]),
                Forms\Components\Section::make('Smart Calendar')
                    ->description('These dates are auto-estimated from the selected crop calendar and can be edited.')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(12)
                    ->schema([
                        Forms\Components\DatePicker::make('expected_germination_from')
                            ->label('Germination From')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('expected_germination_to')
                            ->label('Germination To')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('expected_transplant_date')
                            ->label('Expected Transplant')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('germination_percent')
                            ->label('Germination %')
                            ->numeric()
                            ->suffix('%')
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('expected_harvest_from')
                            ->label('Harvest From')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('expected_harvest_to')
                            ->label('Harvest To')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('actual_harvest_start')
                            ->label('Actual Harvest Start')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('actual_harvest_end')
                            ->label('Actual Harvest End')
                            ->native(false)
                            ->columnSpan(3),
                    ]),
                Forms\Components\Section::make('Area, Growth & Health')
                    ->description('Track crop progress, stage, population, field health, and management status.')
                    ->icon('heroicon-o-chart-bar-square')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('area_planted')
                            ->label('Area Planted')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(3),
                        Forms\Components\Select::make('area_unit')
                            ->label('Area Unit')
                            ->native(false)
                            ->default('acre')
                            ->options([
                                'acre' => 'Acre',
                                'hectare' => 'Hectare',
                                'sqm' => 'Square Metres',
                            ])
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('plant_population')
                            ->label('Plant Population')
                            ->numeric()
                            ->columnSpan(3),
                        Forms\Components\Select::make('growth_stage')
                            ->label('Growth Stage')
                            ->native(false)
                            ->default('planned')
                            ->live()
                            ->options([
                                'planned' => 'Planned',
                                'sown' => 'Sown',
                                'planted' => 'Planted',
                                'germination' => 'Germination',
                                'emerged' => 'Emerged',
                                'vegetative' => 'Vegetative',
                                'flowering' => 'Flowering',
                                'fruiting' => 'Fruiting',
                                'maturity' => 'Maturity',
                                'harvesting' => 'Harvesting',
                                'harvested' => 'Harvested',
                            ])
                            ->columnSpan(3),
                        Forms\Components\Select::make('health_status')
                            ->label('Health')
                            ->native(false)
                            ->default('good')
                            ->options([
                                'excellent' => 'Excellent',
                                'good' => 'Good',
                                'fair' => 'Fair',
                                'poor' => 'Poor',
                                'critical' => 'Critical',
                            ])
                            ->columnSpan(3),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->native(false)
                            ->default('active')
                            ->options([
                                'planned' => 'Planned',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'failed' => 'Failed',
                            ])
                            ->columnSpan(3),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Crop Intelligence Preview')
                    ->description('This preview is generated from crop, stage, health, germination, and harvest timing.')
                    ->icon('heroicon-o-cpu-chip')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('intelligence_preview')
                            ->label('')
                            ->content(function (?CropSeason $record): \Illuminate\Support\HtmlString {
                                if (!$record?->exists) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="text-sm text-gray-500">Save the season first to view crop intelligence preview.</div>'
                                    );
                                }

                                return new \Illuminate\Support\HtmlString(
                                    view('filament.crops.crop-season-intelligence-card', [
                                        'record' => $record,
                                        'compact' => false,
                                    ])->render()
                                );
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\ViewColumn::make('crop_visual')
                    ->label('Visual')
                    ->view('filament.tables.columns.crop-stage-premium')
                    ->toggleable(false),
                Tables\Columns\TextColumn::make('season_code')
                    ->label('Code')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Season')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(CropSeason $record): string =>
                        $record->crop_name . ' • ' . ($record->fieldPartition?->name ?? $record->farmField?->name ?? 'No field'))
                    ->icon('heroicon-o-sun'),
                Tables\Columns\TextColumn::make('growth_progress_percent')
                    ->label('Progress')
                    ->formatStateUsing(fn($state): string => number_format((int) $state) . '%')
                    ->badge()
                    ->color(fn(CropSeason $record): string => match (true) {
                        $record->growth_progress_percent >= 85 => 'success',
                        $record->growth_progress_percent >= 50 => 'info',
                        $record->growth_progress_percent >= 20 => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('planting_date')
                    ->label('Planted')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('days_since_planting')
                    ->label('Age')
                    ->formatStateUsing(fn($state): string => number_format((int) $state) . ' days')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('expected_harvest_from')
                    ->label('Harvest ETA')
                    ->formatStateUsing(fn($state, CropSeason $record): string =>
                        $record->expected_harvest_from
                            ? $record->expected_harvest_from->format('d M Y')
                            : 'N/A')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('harvest_status')
                    ->label('Harvest Status')
                    ->badge()
                    ->color(fn(CropSeason $record): string => match ($record->harvest_status) {
                        'Harvested' => 'success',
                        'Due Soon' => 'warning',
                        'Overdue' => 'danger',
                        'Scheduled' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('growth_stage')
                    ->label('Stage')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state): string => str($state)->replace('_', ' ')->title()),
                Tables\Columns\TextColumn::make('health_status')
                    ->label('Health')
                    ->badge()
                    ->color(fn($state): string => match ($state) {
                        'excellent', 'good' => 'success',
                        'fair' => 'warning',
                        'poor', 'critical' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state): string => str($state)->title()),
                Tables\Columns\TextColumn::make('watering_advice')
                    ->label('Watering')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('root_status')
                    ->label('Roots')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('shoot_status')
                    ->label('Shoots')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('next_action_advice')
                    ->label('Next Action')
                    ->limit(45)
                    ->wrap()
                    ->badge()
                    ->color(fn(CropSeason $record): string => $record->visual_urgency)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_input_cost')
                    ->label('Input Cost')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_harvest_value')
                    ->label('Harvest Value')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state): string => match ($state) {
                        'active' => 'success',
                        'planned' => 'info',
                        'completed' => 'success',
                        'cancelled', 'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state): string => str($state)->title()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'planned' => 'Planned',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('growth_stage')
                    ->options([
                        'planned' => 'Planned',
                        'sown' => 'Sown',
                        'planted' => 'Planted',
                        'germination' => 'Germination',
                        'emerged' => 'Emerged',
                        'vegetative' => 'Vegetative',
                        'flowering' => 'Flowering',
                        'fruiting' => 'Fruiting',
                        'maturity' => 'Maturity',
                        'harvesting' => 'Harvesting',
                        'harvested' => 'Harvested',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('visualIntelligence')
                    ->label('3D View')
                    ->icon('heroicon-o-cube-transparent')
                    ->color('info')
                    ->modalWidth('2xl')
                    ->modalHeading(fn(CropSeason $record): string => 'Crop Intelligence: ' . $record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn(CropSeason $record) => view('filament.crops.crop-season-3d-modal', [
                        'record' => $record,
                    ])),
                Tables\Actions\Action::make('recordGermination')
                    ->label('Germination')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->slideOver()
                    ->form([
                        Forms\Components\DatePicker::make('actual_germination_date')
                            ->label('Actual Germination Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required(),
                        Forms\Components\TextInput::make('germination_percent')
                            ->label('Germination %')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                    ])
                    ->action(function (CropSeason $record, array $data): void {
                        $record->forceFill([
                            'actual_germination_date' => $data['actual_germination_date'],
                            'germination_percent' => $data['germination_percent'],
                            'growth_stage' => 'germination',
                        ])->save();

                        Notification::make()
                            ->title('Germination recorded')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('generateCareTasks')
                    ->label('Care Tasks')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Crop Care Tasks')
                    ->modalDescription('This will create practical care tasks based on this crop season timeline.')
                    ->action(function (CropSeason $record): void {
                        static::generateCareTasksForSeason($record);

                        Notification::make()
                            ->title('Care tasks generated')
                            ->body('Crop care tasks have been created for this season.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(): bool =>
                        auth()->user()?->can('delete crop seasons') ||
                        auth()->user()?->hasRole('Admin') ||
                        auth()->user()?->hasRole('Administrator')),
            ])
            ->emptyStateIcon('heroicon-o-sun')
            ->emptyStateHeading('No crop seasons started')
            ->emptyStateDescription('Create crop seasons for maize, avocados, fodder, orchards, and other crop cycles.');
    }

    public static function applyCropCalendar(?int $cropId, ?string $baseDate, Set $set): void
    {
        if (!$cropId || !$baseDate) {
            return;
        }

        $crop = CropCatalog::query()->find($cropId);

        if (!$crop) {
            return;
        }

        $dates = app(CropCalendarService::class)->datesFor($crop, $baseDate);

        foreach ($dates as $field => $value) {
            $set($field, $value);
        }
    }

    public static function generateCareTasksForSeason(CropSeason $season): void
    {
        $baseDate = $season->planting_date ?: $season->start_date ?: now('Africa/Nairobi');
        $baseDate = Carbon::parse($baseDate);

        $tasks = [
            [
                'days' => 0,
                'type' => 'planting',
                'title' => 'Confirm planting and field setup',
                'instructions' => 'Confirm planting records, area planted, seed rate, spacing, and field condition.',
            ],
            [
                'days' => 7,
                'type' => 'scouting',
                'title' => 'Early crop emergence scouting',
                'instructions' => 'Check germination, emergence, pests, moisture, and weak crop sections.',
            ],
            [
                'days' => 14,
                'type' => 'weeding',
                'title' => 'First weed control check',
                'instructions' => 'Inspect weed pressure and plan hand weeding, herbicide, or mechanical control where needed.',
            ],
            [
                'days' => 28,
                'type' => 'fertilizer',
                'title' => 'Nutrition and top dressing review',
                'instructions' => $season->cropCatalog?->fertilizer_routine ?: 'Check crop nutrition and apply fertiliser/manure where required.',
            ],
            [
                'days' => 42,
                'type' => 'scouting',
                'title' => 'Pest and disease scouting',
                'instructions' => $season->cropCatalog?->spray_routine ?: 'Scout for pest and disease pressure before any chemical application.',
            ],
        ];

        if ($season->expected_harvest_from) {
            $tasks[] = [
                'date' => Carbon::parse($season->expected_harvest_from)->subDays(10),
                'type' => 'harvest',
                'title' => 'Pre-harvest preparation',
                'instructions' => 'Prepare labour, packaging, storage, drying area, and market/usage plan before harvest.',
            ];
        }

        foreach ($tasks as $task) {
            $dueDate = isset($task['date'])
                ? Carbon::parse($task['date'])
                : $baseDate->copy()->addDays((int) $task['days']);

            CropCareTask::query()->firstOrCreate(
                [
                    'crop_season_id' => $season->id,
                    'due_date' => $dueDate->toDateString(),
                    'title' => $task['title'],
                ],
                [
                    'crop_catalog_id' => $season->crop_catalog_id,
                    'task_no' => null,
                    'task_type' => $task['type'],
                    'instructions' => $task['instructions'],
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]
            );
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCropSeasons::route('/'),
            'create' => Pages\CreateCropSeason::route('/create'),
            'edit' => Pages\EditCropSeason::route('/{record}/edit'),
        ];
    }
}
