<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FarmFieldResource\Pages;
use App\Models\FarmField;
use App\Models\Location;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Facades\Schema;

class FarmFieldResource extends Resource
{
    protected static ?string $model = FarmField::class;

    protected static ?string $navigationGroup = 'Crop Farming';

    protected static ?string $navigationLabel = 'Land & Field(s)';

    protected static ?string $modelLabel = 'Farm Field';

    protected static ?string $pluralModelLabel = 'Land & Fields';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'crop-farming/fields';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view crop fields') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create crop fields') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit crop fields') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete crop fields') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Field / Land Identity')
                    ->description('Register land areas, blocks, parcels, and crop production zones.')
                    ->icon('heroicon-o-map')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('field_code')
                            ->label('Field Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('name')
                            ->label('Field / Land Name')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-map-pin')
                            ->placeholder('Dhiwa Farm, Munetho Block, Orchard Area')
                            ->columnSpan(5),
                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->options(fn(): array => static::locationOptions())
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-map-pin')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('total_area')
                            ->label('Total Area')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(3),
                        Forms\Components\Select::make('area_unit')
                            ->label('Area Unit')
                            ->native(false)
                            ->default('acre')
                            ->required()
                            ->options([
                                'acre' => 'Acre',
                                'hectare' => 'Hectare',
                                'sqm' => 'Square Metres',
                            ])
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('soil_type')
                            ->label('Soil Type')
                            ->placeholder('Loam, clay, sandy, black cotton')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('irrigation_type')
                            ->label('Irrigation Type')
                            ->placeholder('Rainfed, drip, sprinkler, flood')
                            ->columnSpan(3),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->native(false)
                            ->default('active')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'fallow' => 'Fallow',
                                'under_preparation' => 'Under Preparation',
                                'inactive' => 'Inactive',
                            ])
                            ->columnSpan(4),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Field Partitions / Blocks')
                    ->description('Divide the land into operational blocks where crop activities can be assigned.')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        Forms\Components\Repeater::make('partitions')
                            ->relationship()
                            ->label('Partitions')
                            ->addActionLabel('Add Partition / Block')
                            ->columns(12)
                            ->defaultItems(0)
                            ->schema([
                                Forms\Components\TextInput::make('partition_code')
                                    ->label('Code')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('name')
                                    ->label('Partition Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Block A, Orchard Zone, Nursery Bed 1')
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('area')
                                    ->label('Area')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\Select::make('area_unit')
                                    ->label('Unit')
                                    ->native(false)
                                    ->default('acre')
                                    ->required()
                                    ->options([
                                        'acre' => 'Acre',
                                        'hectare' => 'Hectare',
                                        'sqm' => 'Square Metres',
                                    ])
                                    ->columnSpan(2),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->native(false)
                                    ->default('vacant')
                                    ->required()
                                    ->options([
                                        'vacant' => 'Vacant',
                                        'under_preparation' => 'Under Preparation',
                                        'planted' => 'Planted',
                                        'nursery' => 'Nursery',
                                        'orchard' => 'Orchard',
                                        'harvested' => 'Harvested',
                                        'fallow' => 'Fallow',
                                    ])
                                    ->columnSpan(2),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notes')
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
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('field_code')
                    ->label('Code')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Field / Land')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(FarmField $record): string =>
                        'Location: ' . $record->location_display)
                    ->icon('heroicon-o-map'),
                Tables\Columns\TextColumn::make('total_area')
                    ->label('Total Area')
                    ->formatStateUsing(fn($state, FarmField $record): string =>
                        number_format((float) $state, 3) . ' ' . $record->area_unit)
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('allocated_area')
                    ->label('Allocated')
                    ->formatStateUsing(fn($state, FarmField $record): string =>
                        number_format((float) $state, 3) . ' ' . $record->area_unit)
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('available_area')
                    ->label('Available')
                    ->formatStateUsing(fn($state, FarmField $record): string =>
                        number_format((float) $state, 3) . ' ' . $record->area_unit)
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('partitions_count')
                    ->label('Partitions')
                    ->counts('partitions')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn(FarmField $record): string => match ($record->status) {
                        'active' => 'success',
                        'under_preparation' => 'warning',
                        'fallow' => 'gray',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'fallow' => 'Fallow',
                        'under_preparation' => 'Under Preparation',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(): bool => auth()->user()?->can('delete crop fields') || auth()->user()?->hasRole('Administrator')),
            ])
            ->emptyStateIcon('heroicon-o-map')
            ->emptyStateHeading('No fields registered')
            ->emptyStateDescription('Add farm fields, land parcels, and assign partitions for planting activities.');
    }

    public static function locationOptions(): array
    {
        if (!Schema::hasTable('locations')) {
            return [];
        }

        $nameColumn = Schema::hasColumn('locations', 'name')
            ? 'name'
            : (Schema::hasColumn('locations', 'location_name') ? 'location_name' : null);

        if (!$nameColumn) {
            return [];
        }

        return Location::query()
            ->orderBy($nameColumn)
            ->pluck($nameColumn, 'id')
            ->toArray();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFarmFields::route('/'),
            'create' => Pages\CreateFarmField::route('/create'),
            'edit' => Pages\EditFarmField::route('/{record}/edit'),
        ];
    }
}
