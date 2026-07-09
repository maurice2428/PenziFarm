<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Livestock\Animals as AnimalsCluster;
use App\Filament\Resources\AnimalResource\Pages;
use App\Filament\Support\LocationForm;
use App\Models\Animal;
use App\Models\AnimalWeight;
use App\Models\Breed;
use App\Models\Location;
use App\Services\AnimalTagGeneratorService;
use App\Services\BreedPurityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AnimalResource extends Resource
{
    protected static ?string $model = Animal::class;

    protected static ?string $cluster = AnimalsCluster::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Manage Animals';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view animals') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view animals') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create animals') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit animals') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete animals') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Animal Identity')
                ->schema([
                    Forms\Components\Select::make('breed_id')
                        ->label('Breed')
                        ->options(
                            Breed::query()
                                ->orderBy('parent_category')
                                ->orderBy('breed_name')
                                ->pluck('breed_name', 'id')
                                ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            if (blank($state)) {
                                $set('species', null);
                                $set('purity_breed_id', null);
                                $set('sire_id', null);
                                $set('dam_id', null);

                                return;
                            }

                            $breed = Breed::find($state);

                            $set('species', $breed?->parent_category);
                            $set('purity_breed_id', $state);
                            $set('sire_id', null);
                            $set('dam_id', null);
                        }),
                    Forms\Components\Placeholder::make('penzi_tag_preview')
                        ->label('Tag Preview')
                        ->content(function (Forms\Get $get): HtmlString {
                            $breedId = $get('breed_id');
                            $dateOfBirth = $get('date_of_birth');

                            $baseStyle = '
                                border: 1px solid #bbf7d0;
                                border-left: 5px solid #15803d;
                                background:
                                    radial-gradient(circle at top right, rgba(34, 197, 94, .16), transparent 42%),
                                    linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
                                padding: 16px;
                                min-height: 115px;
                                box-shadow: 0 10px 24px rgba(21, 128, 61, .08);
                            ';

                            if (blank($breedId) || blank($dateOfBirth)) {
                                return new HtmlString(
                                    '<div style="' . $baseStyle . '">
                                        <div style="color:#166534;font-size:12px;font-weight:900;text-transform:uppercase;">
                                            Automated Identification
                                        </div>
                                        <div style="margin-top:12px;font-size:16px;font-weight:850;color:#374151;">
                                            Your animal tag will appear here
                                        </div>
                                        <div style="margin-top:5px;font-size:12px;color:#6b7280;">
                                            Select breed and date of birth to preview the next Penzi tag.
                                        </div>
                                    </div>'
                                );
                            }

                            $breed = Breed::find($breedId);

                            if (!$breed) {
                                return new HtmlString(
                                    '<div style="' . $baseStyle . 'color:#b91c1c;">
                                        <strong>Unable to prepare tag preview.</strong>
                                    </div>'
                                );
                            }

                            try {
                                $preview = app(AnimalTagGeneratorService::class)
                                    ->previewForBreedAndBirthDate(
                                        $breed,
                                        $dateOfBirth
                                    );

                                $tag = e($preview['tag_number']);
                                $breedName = e($breed->breed_name);
                                $year = e($preview['birth_year']);
                                $sequence = str_pad(
                                    (string) $preview['tag_sequence'],
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                );

                                return new HtmlString(
                                    '<div style="' . $baseStyle . '">
                                        <div style="display:flex;justify-content:space-between;gap:12px;">
                                            <div>
                                                <div style="color:#166534;font-size:11px;font-weight:900;text-transform:uppercase;">
                                                    Next Penzi Tag
                                                </div>
                                                <div style="margin-top:10px;color:#14532d;font-family:monospace;font-size:25px;font-weight:950;">
                                                    ' . $tag . '
                                                </div>
                                            </div>
                                            <div style="padding:6px 9px;background:#dcfce7;border:1px solid #86efac;color:#166534;font-size:10px;font-weight:900;">
                                                PREVIEW
                                            </div>
                                        </div>

                                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:7px;margin-top:15px;">
                                            <div style="padding:7px 8px;border:1px solid #dcfce7;">
                                                <div style="color:#6b7280;font-size:9px;font-weight:900;">BREED</div>
                                                <div style="margin-top:3px;color:#1f2937;font-size:11px;font-weight:850;">' . $breedName . '</div>
                                            </div>
                                            <div style="padding:7px 8px;border:1px solid #dcfce7;">
                                                <div style="color:#6b7280;font-size:9px;font-weight:900;">BIRTH YEAR</div>
                                                <div style="margin-top:3px;color:#1f2937;font-size:11px;font-weight:850;">' . $year . '</div>
                                            </div>
                                            <div style="padding:7px 8px;border:1px solid #dcfce7;">
                                                <div style="color:#6b7280;font-size:9px;font-weight:900;">TALLY</div>
                                                <div style="margin-top:3px;color:#1f2937;font-size:11px;font-weight:850;">#' . $sequence . '</div>
                                            </div>
                                        </div>

                                        <div style="margin-top:11px;color:#6b7280;font-size:10px;">
                                            The tag is permanently reserved only when this animal is saved.
                                        </div>
                                    </div>'
                                );
                            } catch (\Throwable $exception) {
                                return new HtmlString(
                                    '<div style="border:1px solid #fecaca;border-left:5px solid #dc2626;background:#fef2f2;padding:14px;color:#991b1b;">
                                        <strong>Tag preview unavailable.</strong><br>'
                                    . e($exception->getMessage())
                                    . '</div>'
                                );
                            }
                        })
                        ->live()
                        ->columnSpan(1),
                    Forms\Components\Select::make('sex')
                        ->options([
                            'Male' => 'Male',
                            'Female' => 'Female',
                        ])
                        ->required(),
                    Forms\Components\DatePicker::make('date_of_birth')
                        ->label('Date of Birth')
                        ->required()
                        ->maxDate(today())
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set): void {
                            $set('sire_id', null);
                            $set('dam_id', null);
                        }),
                    Forms\Components\Toggle::make('date_of_birth_is_estimated')
                        ->label('Estimated Date of Birth')
                        ->default(false),
                ])
                ->columns(2),
            Forms\Components\Section::make('Animal Classification')
                ->schema([
                    Forms\Components\Select::make('source')
                        ->options([
                            'Born on farm' => 'Born on farm',
                            'Purchased' => 'Purchased',
                        ])
                        ->default('Born on farm')
                        ->required()
                        ->live(),
                    Forms\Components\Select::make('purpose')
                        ->options([
                            'Breeding' => 'Breeding',
                            'Sale' => 'Sale',
                            'Dairy' => 'Dairy',
                            'Production' => 'Production',
                        ])
                        ->default('Sale')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            if ($state === 'Breeding') {
                                $set('is_breeder', true);
                                $set('sale_ready', false);
                            }
                        }),
                    Forms\Components\Toggle::make('is_breeder')
                        ->label('Retained for Breeding')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            if ($state) {
                                $set('sale_ready', false);
                            }
                        }),
                    Forms\Components\Toggle::make('sale_ready')
                        ->label('Ready for Sale')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            if ($state) {
                                $set('is_breeder', false);
                            }
                        }),
                    Forms\Components\Select::make('status')
                        ->options([
                            'Active' => 'Active',
                            'Sold' => 'Sold',
                            'Dead' => 'Dead',
                            'Culled' => 'Culled',
                        ])
                        ->default('Active')
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('valuation_price')
                        ->numeric()
                        ->prefix('KES'),
                ])
                ->columns(2),
            Forms\Components\Section::make('Purchase Details')
                ->schema([
                    Forms\Components\DatePicker::make('bought_on')
                        ->label('Bought On'),
                    Forms\Components\TextInput::make('bought_from')
                        ->label('Bought From')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('seller_phone')
                        ->label('Seller Phone')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('seller_email')
                        ->label('Seller Email')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('purchase_price')
                        ->label('Purchase Price')
                        ->numeric()
                        ->prefix('KES'),
                    Forms\Components\Textarea::make('seller_address')
                        ->label('Seller Address')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('purchase_notes')
                        ->label('Purchase Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(
                    fn(Forms\Get $get): bool =>
                        $get('source') === 'Purchased'
                ),
            Forms\Components\Section::make('Breed Purity & Pedigree Registration')
                ->description(
                    'Foundation animals are approved as 100%. Offspring are calculated from their sire and dam.'
                )
                ->icon('heroicon-o-cpu-chip')
                ->schema([
                    /*
                     * Forms\Components\Select::make('purity_breed_id')
                     *   ->label('Foundation / Target Breed')
                     *   ->options(
                     *       Breed::query()
                     *           ->orderBy('parent_category')
                     *           ->orderBy('breed_name')
                     *           ->pluck('breed_name', 'id')
                     *           ->all()
                     *   )
                     *   ->default(fn (Forms\Get $get) => $get('breed_id'))
                     *   ->searchable()
                     *   ->preload()
                     *   ->required()
                     *   ->live()
                     *   ->helperText(
                     *       'Purity is calculated against this specific breed.'
                     *   ),
                     */
                    Forms\Components\Hidden::make('purity_breed_id')
                        ->default(fn(Forms\Get $get) => $get('breed_id'))
                        ->dehydrated(),
                    Forms\Components\Placeholder::make('purity_breed_display')
                        ->label('Purity Breed')
                        ->content(function (Forms\Get $get): HtmlString {
                            $breed = Breed::find($get('breed_id'));

                            $breedName = $breed?->breed_name ?? 'Select a breed above';

                            return new HtmlString(
                                '<div style="
                border: 1px solid #bbf7d0;
                border-left: 5px solid #15803d;
                background: #f0fdf4;
                padding: 12px 14px;
                border-radius: 8px;
                color: #14532d;
                font-size: 14px;
                font-weight: 900;
            ">
                ' . e($breedName) . '
                <span style="
                    display:block;
                    margin-top:4px;
                    color:#6b7280;
                    font-size:11px;
                    font-weight:600;
                ">
                    Automatically taken from Animal Identity → Breed
                </span>
            </div>'
                            );
                        }),
                    Forms\Components\Toggle::make('is_foundation_animal')
                        ->label('Approved Foundation Animal')
                        ->default(false)
                        ->live()
                        ->helperText(
                            'Use only for verified pure foundation stock. The result becomes 100.00%.'
                        )
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            if ($state) {
                                $set('purity_override_percent', null);
                                $set('purity_status', 'foundation');
                            }
                        }),
                    Forms\Components\Select::make('purity_status')
                        ->label('Purity Verification Type')
                        ->options([
                            'pending' => 'Automatic / Pending Parentage',
                            'calculated' => 'System Calculated',
                            'dna_verified' => 'DNA Verified',
                            'manual_verified' => 'Manual Verified',
                        ])
                        ->default('pending')
                        ->live()
                        ->visible(
                            fn(Forms\Get $get): bool =>
                                !(bool) $get('is_foundation_animal')
                        ),
                    Forms\Components\TextInput::make('purity_override_percent')
                        ->label('Verified Purity Percentage')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.0001)
                        ->suffix('%')
                        ->live(onBlur: true)
                        ->visible(
                            fn(Forms\Get $get): bool =>
                                in_array(
                                    $get('purity_status'),
                                    ['dna_verified', 'manual_verified'],
                                    true
                                ) &&
                                !(bool) $get('is_foundation_animal')
                        ),
                    Forms\Components\DatePicker::make('purity_verified_at')
                        ->label('Verification Date')
                        ->visible(
                            fn(Forms\Get $get): bool =>
                                in_array(
                                    $get('purity_status'),
                                    ['dna_verified', 'manual_verified'],
                                    true
                                )
                        ),
                    Forms\Components\Textarea::make('purity_notes')
                        ->label('Purity Evidence / Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make('purity_preview')
                        ->label('Calculated Breed Purity')
                        ->content(function (Forms\Get $get): HtmlString {
                            $preview = app(BreedPurityService::class)->preview(
                                // targetBreedId: $get('purity_breed_id'),
                                targetBreedId: $get('purity_breed_id') ?: $get('breed_id'),
                                sireId: $get('sire_id'),
                                damId: $get('dam_id'),
                                isFoundationAnimal: (bool) $get('is_foundation_animal'),
                                overridePercent: filled($get('purity_override_percent'))
                                    ? (float) $get('purity_override_percent')
                                    : null,
                            );

                            $color = match ($preview['status']) {
                                'foundation' => '#15803d',
                                'calculated' => '#2563eb',
                                'dna_verified' => '#7c3aed',
                                'manual_verified' => '#a16207',
                                default => '#6b7280',
                            };

                            return new HtmlString(
                                '<div style="
                                    border:1px solid ' . $color . ';
                                    border-left:6px solid ' . $color . ';
                                    border-radius:8px;
                                    background:#ffffff;
                                    padding:14px 16px;
                                    color:' . $color . ';
                                    font-size:15px;
                                    font-weight:900;
                                ">'
                                . e($preview['label'])
                                . '</div>'
                            );
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Death Details')
                ->schema([
                    Forms\Components\DatePicker::make('date_died')
                        ->label('Date Died')
                        ->required(
                            fn(Forms\Get $get): bool =>
                                $get('status') === 'Dead'
                        ),
                    Forms\Components\TextInput::make('cause_of_death')
                        ->label('Cause of Death')
                        ->required(
                            fn(Forms\Get $get): bool =>
                                $get('status') === 'Dead'
                        )
                        ->maxLength(255),
                    Forms\Components\Textarea::make('death_comments')
                        ->label('Comments')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(
                    fn(Forms\Get $get): bool =>
                        $get('status') === 'Dead'
                ),
            Forms\Components\Section::make('Culling Details')
                ->schema([
                    Forms\Components\DatePicker::make('date_culled')
                        ->label('Date Culled')
                        ->required(
                            fn(Forms\Get $get): bool =>
                                $get('status') === 'Culled'
                        ),
                    Forms\Components\TextInput::make('culling_reason')
                        ->label('Reason for Culling')
                        ->required(
                            fn(Forms\Get $get): bool =>
                                $get('status') === 'Culled'
                        )
                        ->maxLength(255),
                    Forms\Components\Textarea::make('culling_comments')
                        ->label('Comments')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(
                    fn(Forms\Get $get): bool =>
                        $get('status') === 'Culled'
                ),
            Forms\Components\Section::make('Lineage & Location')
                ->description(
                    'Parents can be different breeds, but must match the animal species and be at least one year older.'
                )
                ->schema([
                    Forms\Components\Select::make('sire_id')
                        ->label('Sire (Father)')
                        ->options(function (
                            Forms\Get $get,
                            ?Animal $record,
                        ): array {
                            $species = $get('species');
                            $dob = $get('date_of_birth');
                            $damId = $get('dam_id');

                            $query = Animal::query()
                                ->where('sex', 'Male')
                                ->where('is_archived', false)
                                ->whereNotNull('date_of_birth');

                            if ($species) {
                                $query->where('species', $species);
                            }

                            if ($record) {
                                $query->whereKeyNot($record->id);
                            }

                            if ($damId) {
                                $query->whereKeyNot($damId);
                            }

                            $cutoffDate = $dob
                                ? Carbon::parse($dob)->subYear()->toDateString()
                                : now()->subYear()->toDateString();

                            return $query
                                ->whereDate('date_of_birth', '<=', $cutoffDate)
                                ->orderBy('tag_number')
                                ->pluck('tag_number', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->live()
                        ->helperText(
                            'Compatible male animals of the same species, at least one year older.'
                        ),
                    Forms\Components\Select::make('dam_id')
                        ->label('Dam (Mother)')
                        ->options(function (
                            Forms\Get $get,
                            ?Animal $record,
                        ): array {
                            $species = $get('species');
                            $dob = $get('date_of_birth');
                            $sireId = $get('sire_id');

                            $query = Animal::query()
                                ->where('sex', 'Female')
                                ->where('is_archived', false)
                                ->whereNotNull('date_of_birth');

                            if ($species) {
                                $query->where('species', $species);
                            }

                            if ($record) {
                                $query->whereKeyNot($record->id);
                            }

                            if ($sireId) {
                                $query->whereKeyNot($sireId);
                            }

                            $cutoffDate = $dob
                                ? Carbon::parse($dob)->subYear()->toDateString()
                                : now()->subYear()->toDateString();

                            return $query
                                ->whereDate('date_of_birth', '<=', $cutoffDate)
                                ->orderBy('tag_number')
                                ->pluck('tag_number', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->live()
                        ->helperText(
                            'Compatible female animals of the same species, at least one year older.'
                        ),
                    Forms\Components\Select::make('current_location_id')
                        ->label('Current Location')
                        ->relationship(
                            name: 'location',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn(
                                \Illuminate\Database\Eloquent\Builder $query
                            ) => $query
                                ->where('is_active', true)
                                ->orderByDesc('is_default')
                                ->orderBy('name')
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn(Location $location): string =>
                                $location->display_name
                        )
                        ->default(
                            fn(): ?int => Location::query()
                                ->where('is_active', true)
                                ->where('is_default', true)
                                ->value('id')
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->hintAction(
                            FormAction::make('refreshLocations')
                                ->label('Refresh')
                                ->icon('heroicon-m-arrow-path')
                                ->color('gray')
                                ->action(function (
                                    Forms\Get $get,
                                    Forms\Set $set
                                ): void {
                                    $set(
                                        'current_location_id',
                                        $get('current_location_id')
                                    );

                                    Notification::make()
                                        ->title('Location options refreshed')
                                        ->success()
                                        ->send();
                                })
                        )
                        ->createOptionForm(
                            LocationForm::quickCreateSchema()
                        )
                        ->createOptionUsing(function (array $data): int {
                            unset($data['map_picker']);

                            $data['created_by'] = auth()->id();
                            $data['updated_by'] = auth()->id();
                            $data['is_active'] = $data['is_active'] ?? true;

                            return Location::create($data)->getKey();
                        })
                        ->createOptionAction(
                            fn(FormAction $action): FormAction => $action
                                ->slideOver()
                                ->modalWidth(MaxWidth::TwoExtraLarge)
                                ->stickyModalFooter()
                                ->modalHeading('Add Animal Location')
                                ->modalDescription(
                                    'Create a station without leaving this animal form.'
                                )
                                ->modalSubmitActionLabel('Save Location')
                                ->modalCancelActionLabel('Cancel')
                        ),
                    Forms\Components\Textarea::make('notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return static::getAnimalTable($table, archivedView: false);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getAnimalTable(
        Table $table,
        bool $archivedView = false
    ): Table {
        $table = $table
            ->recordUrl(
                fn(Animal $record): string =>
                    static::getUrl('edit', ['record' => $record])
            )
            ->columns([
                Tables\Columns\TextColumn::make('tag_number')
                    ->label('Tag')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('breed.breed_name')
                    ->label('Breed')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('breed_purity_percent')
                    ->label('Purity')
                    ->badge()
                    ->state(function (Animal $record): string {
                        if ($record->breed_purity_percent === null) {
                            return 'Pending';
                        }

                        return number_format(
                            (float) $record->breed_purity_percent,
                            2
                        ) . '%';
                    })
                    ->color(function (Animal $record): string {
                        return match ($record->purity_status) {
                            'foundation' => 'success',
                            'calculated' => 'info',
                            'dna_verified' => 'primary',
                            'manual_verified' => 'warning',
                            default => 'gray',
                        };
                    })
                    ->tooltip(
                        fn(Animal $record): string =>
                            str($record->purity_status)
                                ->replace('_', ' ')
                                ->title()
                                ->toString()
                    ),
                Tables\Columns\TextColumn::make('current_weight')
                    ->label('Weight')
                    ->badge()
                    ->icon(fn(Animal $record) => match (
                        self::getLatestWeightTrend($record)
                    ) {
                        'gaining' => 'heroicon-o-arrow-trending-up',
                        'losing' => 'heroicon-o-arrow-trending-down',
                        'stable' => 'heroicon-o-minus',
                        'first' => 'heroicon-o-scale',
                        default => 'heroicon-o-scale',
                    })
                    ->color(fn(Animal $record) => match (
                        self::getLatestWeightTrend($record)
                    ) {
                        'gaining' => 'success',
                        'losing' => 'danger',
                        'stable' => 'warning',
                        'first' => 'info',
                        default => 'gray',
                    })
                    ->state(function (Animal $record): string {
                        $weight = self::getLatestWeight($record);

                        return $weight
                            ? number_format(
                                (float) $weight->weight_kg,
                                2
                            ) . ' KG'
                            : 'No Weight';
                    })
                    ->url(function (Animal $record): ?string {
                        $weight = self::getLatestWeight($record);

                        return $weight
                            ? AnimalWeightResource::getUrl(
                                'view',
                                ['record' => $weight->id]
                            )
                            : null;
                    })
                    ->openUrlInNewTab()
                    ->tooltip('Click to view weight history')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('species')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sex')
                    ->badge(),
                Tables\Columns\TextColumn::make('age_display')
                    ->label('Age')
                    ->state(function (Animal $record): string {
                        if (blank($record->date_of_birth)) {
                            return '-';
                        }

                        $dob = Carbon::parse($record->date_of_birth);

                        if ($dob->isFuture()) {
                            return 'Invalid DOB';
                        }

                        $age = $dob->diffForHumans(now(), [
                            'parts' => 2,
                            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                        ]);

                        return $record->date_of_birth_is_estimated
                            ? 'Approx. ' . $age
                            : $age;
                    })
                    ->badge()
                    ->color(
                        fn(Animal $record): string =>
                            $record->date_of_birth_is_estimated
                                ? 'warning'
                                : 'success'
                    )
                    ->toggleable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_breeder')
                    ->label('Breeder')
                    ->boolean(),
                Tables\Columns\IconColumn::make('sale_ready')
                    ->label('Sale Ready')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_archived')
                    ->label('Archived')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->default('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('valuation_price')
                    ->label('Valuation')
                    ->money('KES')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('breed_id')
                    ->label('Breed')
                    ->options(
                        fn(): array => Breed::query()
                            ->orderBy('parent_category')
                            ->orderBy('breed_name')
                            ->pluck('breed_name', 'id')
                            ->all()
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('purity_status')
                    ->label('Purity Status')
                    ->options([
                        'foundation' => 'Foundation Stock',
                        'calculated' => 'Calculated',
                        'dna_verified' => 'DNA Verified',
                        'manual_verified' => 'Manual Verified',
                        'pending' => 'Pending',
                    ]),
                Tables\Filters\SelectFilter::make('species')
                    ->options([
                        'Sheep' => 'Sheep',
                        'Goat' => 'Goat',
                        'Cattle' => 'Cattle',
                        'Poultry' => 'Poultry',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Sold' => 'Sold',
                        'Dead' => 'Dead',
                        'Culled' => 'Culled',
                    ]),
                Tables\Filters\TernaryFilter::make('is_breeder')
                    ->label('Breeder'),
                Tables\Filters\TernaryFilter::make('sale_ready')
                    ->label('Sale Ready'),
                Tables\Filters\TernaryFilter::make('is_archived')
                    ->label('Archived'),
            ])
            ->actions([
                Tables\Actions\Action::make('viewAnimalProfile')
                    ->icon('heroicon-o-identification')
                    ->iconButton()
                    ->tooltip('View pedigree and animal profile')
                    ->color('primary')
                    ->visible(
                        fn(): bool => auth()->user()?->can('view animals') ?? false
                    )
                    ->url(
                        fn(Animal $record): string => static::getUrl('profile', [
                            'record' => $record,
                        ])
                    ),
                Tables\Actions\Action::make('generateAnimalProfilePdf')
                    ->icon('heroicon-o-document-arrow-down')
                    ->iconButton()
                    ->tooltip('Generate two-page profile PDF')
                    ->color('danger')
                    ->visible(
                        fn(): bool => auth()->user()?->can('view animals') ?? false
                    )
                    ->action(
                        fn(Animal $record) => redirect()->route(
                            'animals.profile.pdf',
                            ['animal' => $record->getKey()]
                        )
                    ),
                Tables\Actions\Action::make('archive')
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(
                        fn(Animal $record): bool =>
                            (auth()->user()?->can('archive animals') ?? false) &&
                            !$archivedView &&
                            !$record->is_archived
                    )
                    ->action(function (Animal $record, $livewire): void {
                        $record->update(['is_archived' => true]);

                        Notification::make()
                            ->success()
                            ->title('Animal archived successfully.')
                            ->send();

                        $livewire->redirect(
                            request()->header('Referer') ?? url()->previous(),
                            navigate: true
                        );
                    }),
                Tables\Actions\Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        fn(Animal $record): bool =>
                            (auth()->user()?->can('restore animals') ?? false) &&
                            $archivedView &&
                            $record->is_archived
                    )
                    ->action(function (Animal $record, $livewire): void {
                        $record->update(['is_archived' => false]);

                        Notification::make()
                            ->success()
                            ->title('Animal restored successfully.')
                            ->send();

                        $livewire->redirect(
                            request()->header('Referer') ?? url()->previous(),
                            navigate: true
                        );
                    }),
                static::makeSafeDeleteAction(),
            ])
            ->defaultSort('created_at', 'desc');

        if ($archivedView) {
            return $table->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('restore_animals')
                        ->label('Restore Selected')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            Animal::query()
                                ->whereIn('id', $records->pluck('id'))
                                ->update(['is_archived' => false]);

                            Notification::make()
                                ->success()
                                ->title('Selected animals restored successfully.')
                                ->send();
                        }),
                    static::makeSafeDeleteBulkAction(),
                ])->label('Bulk Actions'),
            ]);
        }

        return $table->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\BulkAction::make('archive_animals')
                    ->label('Archive Selected')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        Animal::query()
                            ->whereIn('id', $records->pluck('id'))
                            ->update(['is_archived' => true]);

                        Notification::make()
                            ->success()
                            ->title('Selected animals archived successfully.')
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('printAnimalsPdf')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->deselectRecordsAfterCompletion()
                    ->visible(
                        fn(): bool =>
                            auth()->user()?->can('export animals') ?? false
                    )
                    ->action(function (Collection $records) {
                        if ($records->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('No animals selected.')
                                ->send();

                            return null;
                        }

                        $user = auth()->user();

                        $generatedByRole = $user?->getRoleNames()?->first()
                            ?? 'User';

                        $farmName = setting('farm.name', 'Penzi Farm');

                        $verificationText = $farmName
                            . ' Animal Report | Generated by: '
                            . ($user?->name ?? 'System')
                            . ' (' . $generatedByRole . ')'
                            . ' | Date: '
                            . now('Africa/Nairobi')->format('Y-m-d H:i:s')
                            . ' EAT'
                            . ' | Total Records: '
                            . $records->count();

                        $qrImage = null;

                        try {
                            $qrImage = 'data:image/png;base64,'
                                . base64_encode(
                                    QrCode::format('png')
                                        ->size(120)
                                        ->margin(1)
                                        ->generate($verificationText)
                                );
                        } catch (\Throwable) {
                            $qrImage = null;
                        }

                        $animals = Animal::query()
                            ->with([
                                'breed',
                                'location',
                                'latestWeight',
                                'purityBreed',
                            ])
                            ->whereIn('id', $records->pluck('id'))
                            ->orderBy('tag_number')
                            ->get();

                        $pdf = Pdf::loadView('pdf.animals-bulk-report', [
                            'animals' => $animals,
                            'generatedBy' => $user,
                            'generatedByRole' => $generatedByRole,
                            'verificationText' => $verificationText,
                            'qrImage' => $qrImage,
                            'largeReportMode' => $records->count() >= 300,
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
                            'animal-bulk-report-'
                                . now('Africa/Nairobi')->format('Ymd_His')
                                . '.pdf'
                        );
                    }),
                static::makeSafeDeleteBulkAction(),
            ])->label('Bulk Actions'),
        ]);
    }

    private static function makeSafeDeleteAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('deleteAnimalSafely')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete animal permanently?')
            ->modalDescription(function (Animal $record): string {
                $blockers = static::deletionBlockers($record);

                if ($blockers !== []) {
                    return 'This animal cannot be deleted because it has linked records: '
                        . static::formatBlockers($blockers)
                        . '. Archive it instead to preserve its health, breeding, laboratory, and financial history.';
                }

                return 'This permanently deletes the animal record. This action cannot be undone.';
            })
            ->modalSubmitActionLabel('Delete Animal')
            ->visible(
                fn(): bool => auth()->user()?->can('delete animals') ?? false
            )
            ->action(function (Animal $record): void {
                static::deleteAnimalSafely($record);
            });
    }

    private static function makeSafeDeleteBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('deleteSelectedAnimalsSafely')
            ->label('Delete Selected')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete selected animals permanently?')
            ->modalDescription(
                'Only animals without linked clinical, laboratory, treatment, health, weight, breeding, or sales records will be deleted. Protected animals are skipped and remain available for archiving.'
            )
            ->modalSubmitActionLabel('Delete Eligible Animals')
            ->deselectRecordsAfterCompletion()
            ->visible(
                fn(): bool => auth()->user()?->can('delete animals') ?? false
            )
            ->action(function (Collection $records): void {
                $deleted = [];
                $skipped = [];

                foreach ($records as $record) {
                    $blockers = static::deletionBlockers($record);

                    if ($blockers !== []) {
                        $skipped[$record->tag_number] = static::formatBlockers($blockers);

                        continue;
                    }

                    if (static::deleteAnimalSafely($record, false)) {
                        $deleted[] = $record->tag_number;
                    } else {
                        $skipped[$record->tag_number] = 'database integrity protection';
                    }
                }

                if ($deleted !== []) {
                    Notification::make()
                        ->success()
                        ->title(count($deleted) . ' animal(s) deleted successfully.')
                        ->body('Deleted: ' . implode(', ', array_slice($deleted, 0, 12)))
                        ->send();
                }

                if ($skipped !== []) {
                    $summary = collect($skipped)
                        ->take(6)
                        ->map(
                            fn(string $reason, string $tag): string => $tag . ' — ' . $reason
                        )
                        ->implode("\n");

                    Notification::make()
                        ->warning()
                        ->title(count($skipped) . ' animal(s) were protected and not deleted.')
                        ->body($summary . (count($skipped) > 6 ? "\nAdditional protected animals were skipped." : ''))
                        ->persistent()
                        ->send();
                }

                if ($deleted === [] && $skipped === []) {
                    Notification::make()
                        ->warning()
                        ->title('No animals were selected for deletion.')
                        ->send();
                }
            });
    }

    private static function deleteAnimalSafely(
        Animal $animal,
        bool $notify = true
    ): bool {
        $blockers = static::deletionBlockers($animal);

        if ($blockers !== []) {
            if ($notify) {
                Notification::make()
                    ->danger()
                    ->title('Animal cannot be deleted.')
                    ->body(
                        $animal->tag_number
                        . ' has linked records: '
                        . static::formatBlockers($blockers)
                        . '. Archive it instead to retain the full farm history.'
                    )
                    ->persistent()
                    ->send();
            }

            return false;
        }

        try {
            DB::transaction(function () use ($animal): void {
                $animal->delete();
            });

            if ($notify) {
                Notification::make()
                    ->success()
                    ->title('Animal deleted successfully.')
                    ->body($animal->tag_number . ' was permanently deleted.')
                    ->send();
            }

            return true;
        } catch (QueryException $exception) {
            if ($notify) {
                Notification::make()
                    ->danger()
                    ->title('Animal could not be deleted.')
                    ->body(
                        'A linked database record still protects '
                        . $animal->tag_number
                        . '. Archive the animal instead.'
                    )
                    ->persistent()
                    ->send();
            }

            return false;
        }
    }

    private static function deletionBlockers(Animal $animal): array
    {
        $checks = [
            ['animal_clinical_cases', 'animal_id', 'Sick cases'],
            ['animal_lab_requests', 'animal_id', 'Laboratory requests'],
            ['animal_treatment_records', 'animal_id', 'Treatment records'],
            ['animal_health_records', 'animal_id', 'Legacy health records'],
            ['animal_weights', 'animal_id', 'Weight records'],
            ['health_administration_animals', 'animal_id', 'Health administrations'],
            ['animal_events', 'animal_id', 'Animal events'],
            ['breeding_records', 'female_animal_id', 'Breeding records as dam'],
            ['breeding_records', 'male_animal_id', 'Breeding records as sire'],
            ['sales_invoice_items', 'animal_id', 'Sales invoice items'],
            ['sales_invoice_animal_items', 'animal_id', 'Sales invoice animal items'],
        ];

        $blockers = [];

        foreach ($checks as [$table, $column, $label]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            $count = DB::table($table)
                ->where($column, $animal->getKey())
                ->count();

            if ($count > 0) {
                $blockers[$label] = $count;
            }
        }

        return $blockers;
    }

    private static function formatBlockers(array $blockers): string
    {
        return collect($blockers)
            ->map(
                fn(int $count, string $label): string => $label . ': ' . $count
            )
            ->implode(' · ');
    }

    protected static function getLatestWeight(
        Animal $animal
    ): ?AnimalWeight {
        return AnimalWeight::query()
            ->where('animal_id', $animal->id)
            ->whereNull('deleted_at')
            ->latest('recorded_at')
            ->latest('id')
            ->first();
    }

    protected static function getLatestWeightTrend(
        Animal $animal
    ): string {
        return self::getLatestWeight($animal)?->trend ?? 'none';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnimals::route('/'),
            'create' => Pages\CreateAnimal::route('/create'),
            'edit' => Pages\EditAnimal::route('/{record}/edit'),
            'profile' => Pages\AnimalProfile::route('/{record}/profile'),
        ];
    }
}
