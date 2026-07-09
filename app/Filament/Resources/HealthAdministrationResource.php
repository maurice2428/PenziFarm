<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HealthAdministrationResource\Pages;
use App\Models\Animal;
use App\Models\Breed;
use App\Models\HealthAdministration;
use App\Models\HealthProduct;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class HealthAdministrationResource extends Resource
{
    protected static ?string $model = HealthAdministration::class;

    protected static ?string $navigationGroup = 'Animal Health';

    protected static ?string $navigationLabel = 'Administration(s)';

    protected static ?string $modelLabel = 'Health Administration';

    protected static ?string $pluralModelLabel = 'Health Administrations';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view health administrations') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create health administrations') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit health administrations') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete health administrations') ?? false;
    }

    protected static function activeAnimalIdsForBreed(?int $breedId): array
    {
        if (!$breedId) {
            return [];
        }

        return Animal::query()
            ->where('status', 'Active')
            ->where('breed_id', $breedId)
            ->orderBy('tag_number')
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();
    }

    protected static function recalculateFields(Set $set, Get $get): void
    {
        $animalIds = $get('animal_ids') ?? [];
        $animalCount = is_array($animalIds) ? count($animalIds) : 0;

        $dosage = (float) ($get('dosage_per_animal') ?? 0);

        $set('animal_count', $animalCount);
        $set('total_quantity_used', $animalCount * $dosage);

        $product = HealthProduct::find($get('health_product_id'));

        if ($product && $get('administered_at')) {
            $set(
                'next_due_date',
                $product->calculateNextDueDate($get('administered_at'))?->toDateString()
            );
        }
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Health Product & Date')
                ->description('Select the registered vaccine, dewormer, dipping chemical, or treatment drug.')
                ->icon('heroicon-o-beaker')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                ->schema([
                    Forms\Components\Select::make('health_product_id')
                        ->label('Health Product')
                        ->options(fn() => HealthProduct::query()
                            ->where('status', 'active')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->prefixIcon('heroicon-o-beaker')
                        ->helperText('Only active registered health products appear here.')
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            $product = HealthProduct::find($state);

                            if (!$product) {
                                return;
                            }

                            $set('dosage_per_animal', (float) $product->dosage_per_animal);

                            if ($get('administered_at')) {
                                $set(
                                    'next_due_date',
                                    $product->calculateNextDueDate($get('administered_at'))?->toDateString()
                                );
                            }

                            self::recalculateFields($set, $get);
                        }),
                    Forms\Components\DatePicker::make('administered_at')
                        ->label('Date Administered')
                        ->default(now('Africa/Nairobi'))
                        ->required()
                        ->live()
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            $product = HealthProduct::find($get('health_product_id'));

                            if ($product) {
                                $set(
                                    'next_due_date',
                                    $product->calculateNextDueDate($state)?->toDateString()
                                );
                            }
                        }),
                    Forms\Components\TextInput::make('administered_by')
                        ->label('Administered By')
                        ->default(fn() => auth()->user()?->name)
                        ->prefixIcon('heroicon-o-user'),
                ]),
            Forms\Components\Section::make('Animal Selection')
                ->description('Choose a breed, then either select all active animals in that breed or pick specific tags.')
                ->icon('heroicon-o-identification')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                ->schema([
                    Forms\Components\Select::make('breed_id')
                        ->label('Breed')
                        ->options(fn() => Breed::query()
                            ->orderBy('breed_name')
                            ->pluck('breed_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->dehydrated(false)
                        ->required()
                        ->prefixIcon('heroicon-o-squares-2x2')
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            if ($get('animal_selection_mode') === 'all_breed') {
                                $set('animal_ids', self::activeAnimalIdsForBreed((int) $state));
                            } else {
                                $set('animal_ids', []);
                            }

                            self::recalculateFields($set, $get);
                        }),
                    Forms\Components\Radio::make('animal_selection_mode')
                        ->label('Selection Mode')
                        ->options([
                            'all_breed' => 'Select all active animals in selected breed',
                            'specific_tags' => 'Select specific animal tags',
                        ])
                        ->default('specific_tags')
                        ->live()
                        ->dehydrated(false)
                        ->inline(false)
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            if ($state === 'all_breed') {
                                $set('animal_ids', self::activeAnimalIdsForBreed((int) $get('breed_id')));
                            }

                            if ($state === 'specific_tags') {
                                $set('animal_ids', []);
                            }

                            self::recalculateFields($set, $get);
                        })
                        ->columnSpan(2),
                    Forms\Components\Select::make('animal_ids')
                        ->label('Animal Tags')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->prefixIcon('heroicon-o-identification')
                        ->helperText('Search by tag number. Animals are grouped by breed.')
                        ->options(function (Get $get): array {
                            $breedId = $get('breed_id');

                            return Animal::query()
                                ->with('breed')
                                ->where('status', 'Active')
                                ->when($breedId, fn($query) => $query->where('breed_id', $breedId))
                                ->orderBy('tag_number')
                                ->get()
                                ->groupBy(fn(Animal $animal) => $animal->breed?->breed_name ?? 'Unknown Breed')
                                ->map(fn($animals) => $animals
                                    ->mapWithKeys(fn(Animal $animal) => [
                                        $animal->id => $animal->tag_number
                                            . ' • '
                                            . ($animal->sex ?? 'N/A')
                                            . ' • '
                                            . ($animal->breed?->breed_name ?? 'Unknown Breed'),
                                    ])
                                    ->toArray())
                                ->toArray();
                        })
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            self::recalculateFields($set, $get);
                        })
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make('animal_selection_summary')
                        ->label('Selection Summary')
                        ->content(function (Get $get): string {
                            $count = count($get('animal_ids') ?? []);
                            $mode = $get('animal_selection_mode') === 'all_breed'
                                ? 'All active animals in selected breed'
                                : 'Specific selected tags';

                            return "{$mode}. Selected animals: {$count}.";
                        })
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Dosage & Automatic Stock Deduction')
                ->description('The system calculates total quantity used and deducts it from stock automatically.')
                ->icon('heroicon-o-calculator')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    Forms\Components\TextInput::make('animal_count')
                        ->label('Animal Count')
                        ->numeric()
                        ->readOnly()
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-users'),
                    Forms\Components\TextInput::make('dosage_per_animal')
                        ->label('Dosage Per Animal')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->prefixIcon('heroicon-o-scale')
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            self::recalculateFields($set, $get);
                        }),
                    Forms\Components\TextInput::make('total_quantity_used')
                        ->label('Total Quantity Used')
                        ->numeric()
                        ->readOnly()
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-arrow-down-tray'),
                    Forms\Components\DatePicker::make('next_due_date')
                        ->label('Next Due Date')
                        ->readOnly()
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-clock')
                        ->helperText('Auto-calculated from the product frequency.'),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('administered_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('administered_at')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-beaker'),
                Tables\Columns\TextColumn::make('product.type_label')
                    ->label('Type')
                    ->badge()
                    ->color(fn($record) => match ($record->product?->type) {
                        'vaccine' => 'success',
                        'dewormer' => 'warning',
                        'dip' => 'info',
                        'treatment' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('animal_count')
                    ->label('Animals')
                    ->badge()
                    ->icon('heroicon-o-users'),
                Tables\Columns\TextColumn::make('dosage_per_animal')
                    ->label('Dosage')
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2) . ' ' . ($record->product?->dosage_unit ?? ''))
                    ->icon('heroicon-o-scale'),
                Tables\Columns\TextColumn::make('total_quantity_used')
                    ->label('Stock Deducted')
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2) . ' ' . ($record->product?->dosage_unit ?? ''))
                    ->color('danger')
                    ->icon('heroicon-o-arrow-down-tray'),
                Tables\Columns\TextColumn::make('next_due_date')
                    ->label('Next Due')
                    ->date('d M Y')
                    ->color(fn($record) => $record->next_due_date?->isPast() ? 'danger' : 'success')
                    ->icon('heroicon-o-clock'),
                Tables\Columns\TextColumn::make('administered_by')
                    ->label('By')
                    ->toggleable(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected Administrations')
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected Health Administrations?')
                        ->modalDescription(
                            'Selected records will be deleted. Any existing deletion observers, including stock-reversal logic, will run for each selected record.'
                        )
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete administration record?')
                    ->modalDescription('This will also reverse/delete the related stock deduction movement.')
                    ->icon('heroicon-o-trash'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHealthAdministrations::route('/'),
            'create' => Pages\CreateHealthAdministration::route('/create'),
            'edit' => Pages\EditHealthAdministration::route('/{record}/edit'),
        ];
    }
}
