<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnimalFeedingResource\Pages;
use App\Models\Animal;
use App\Models\AnimalFeeding;
use App\Models\Breed;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Services\Inventory\InventoryLedgerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AnimalFeedingResource extends Resource
{
    protected static ?string $model = AnimalFeeding::class;

    protected static ?string $navigationGroup = 'Livestock';

    protected static ?string $navigationLabel = 'Feeding';

    protected static ?string $modelLabel = 'Animal Feeding';

    protected static ?string $pluralModelLabel = 'Animal Feedings';

    protected static ?string $navigationIcon = 'heroicon-o-cake';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'livestock/animal-feeding';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view animal feedings')
            || auth()->user()?->hasRole('Admin')
            || auth()->user()?->hasRole('Administrator')
            || false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create animal feedings')
            || auth()->user()?->hasRole('Admin')
            || auth()->user()?->hasRole('Administrator')
            || false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Feeding Target')
                    ->description('Select animals to feed by specific tags, breed, location, or all active animals.')
                    ->icon('heroicon-o-user-group')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('feeding_no')
                            ->label('Feeding No.')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(3),

                        Forms\Components\DatePicker::make('feeding_date')
                            ->label('Feeding Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->columnSpan(3),

                        Forms\Components\Select::make('target_type')
                            ->label('Feed Target')
                            ->default('selected_animals')
                            ->required()
                            ->live()
                            ->native(false)
                            ->options([
                                'selected_animals' => 'Selected Animal Tags',
                                'breed' => 'All Active Animals in Breed',
                                'location' => 'All Active Animals in Location',
                                'all_active' => 'All Active Animals',
                            ])
                            ->afterStateUpdated(function (Set $set): void {
                                $set('animal_ids', []);
                                $set('breed_id', null);
                                $set('location_id', null);
                            })
                            ->prefixIcon('heroicon-o-adjustments-horizontal')
                            ->columnSpan(3),

                        Forms\Components\Placeholder::make('target_preview')
                            ->label('Animals Matched')
                            ->content(fn (Get $get): string =>
                                number_format(static::resolveAnimalCount($get)) . ' active animal(s)'
                            )
                            ->columnSpan(3),

                        Forms\Components\Select::make('animal_ids')
                            ->label('Animal Tags')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => static::animalOptions())
                            ->visible(fn (Get $get): bool => $get('target_type') === 'selected_animals')
                            ->required(fn (Get $get): bool => $get('target_type') === 'selected_animals')
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('breed_id')
                            ->label('Breed')
                            ->options(fn (): array => Breed::query()
                                ->orderBy('breed_name')
                                ->pluck('breed_name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('target_type') === 'breed')
                            ->required(fn (Get $get): bool => $get('target_type') === 'breed')
                            ->columnSpan(6),

                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->options(fn (): array => Location::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('target_type') === 'location')
                            ->required(fn (Get $get): bool => $get('target_type') === 'location')
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('notes')
                            ->label('Feeding Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Feed Items')
                    ->description('Quantities entered here will automatically reduce stock through stock movements.')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Feed Issued')
                            ->dehydrated(false)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(12)
                            ->schema([
                                Forms\Components\Select::make('inventory_item_id')
                                    ->label('Feed / Stock Item')
                                    ->options(fn (): array => static::feedItemOptions())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                                        $item = InventoryItem::query()->find($state);

                                        if (! $item) {
                                            return;
                                        }

                                        $set('unit', $item->unit);
                                        $set('unit_cost', number_format((float) $item->unit_cost, 2, '.', ''));
                                    })
                                    ->columnSpan(5),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity Issued')
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('unit')
                                    ->label('Unit')
                                    ->readOnly()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->prefix('KES')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\Placeholder::make('available_stock')
                                    ->label('Available')
                                    ->content(function (Get $get): string {
                                        $item = InventoryItem::query()->find($get('inventory_item_id'));

                                        if (! $item) {
                                            return '-';
                                        }

                                        return number_format((float) $item->current_stock, 3) . ' ' . $item->unit;
                                    })
                                    ->columnSpan(2),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Line Notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('feeding_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('feeding_no')
                    ->label('Feeding No.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag'),

                Tables\Columns\TextColumn::make('feeding_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),

                Tables\Columns\TextColumn::make('target_type')
                    ->label('Target')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => str($state)->replace('_', ' ')->title())
                    ->color('info'),

                Tables\Columns\TextColumn::make('breed.breed_name')
                    ->label('Breed')
                    ->placeholder('-')
                    ->badge(),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->placeholder('-')
                    ->badge(),

                Tables\Columns\TextColumn::make('total_animals')
                    ->label('Animals')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('total_feed_quantity')
                    ->label('Feed Qty')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Cost')
                    ->money('KES')
                    ->sortable(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function animalOptions(): array
    {
        return Animal::query()
            ->with('breed')
            ->where('status', 'Active')
            ->where(function ($query): void {
                $query->where('is_archived', false)
                    ->orWhereNull('is_archived');
            })
            ->orderBy('tag_number')
            ->get()
            ->mapWithKeys(function (Animal $animal): array {
                $breed = $animal->breed?->breed_name ?? 'No Breed';
                return [$animal->id => "{$animal->tag_number} | {$breed} | {$animal->species}"];
            })
            ->toArray();
    }

    public static function feedItemOptions(): array
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (InventoryItem $item): array {
                return [
                    $item->id => $item->name
                        . ' | '
                        . str($item->category)->replace('_', ' ')->title()
                        . ' | Stock: '
                        . number_format((float) $item->current_stock, 3)
                        . ' '
                        . $item->unit,
                ];
            })
            ->toArray();
    }

    public static function resolveAnimalCount(Get $get): int
    {
        $query = Animal::query()
            ->where('status', 'Active')
            ->where(function ($query): void {
                $query->where('is_archived', false)
                    ->orWhereNull('is_archived');
            });

        return match ($get('target_type')) {
            'breed' => $get('breed_id')
                ? (clone $query)->where('breed_id', $get('breed_id'))->count()
                : 0,

            'location' => $get('location_id')
                ? (clone $query)->where('current_location_id', $get('location_id'))->count()
                : 0,

            'all_active' => $query->count(),

            default => count((array) ($get('animal_ids') ?? [])),
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnimalFeedings::route('/'),
            'create' => Pages\CreateAnimalFeeding::route('/create'),
        ];
    }
}
