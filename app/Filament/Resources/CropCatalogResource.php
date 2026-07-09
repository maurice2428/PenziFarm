<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropCatalogResource\Pages;
use App\Models\CropCatalog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class CropCatalogResource extends Resource
{
    protected static ?string $model = CropCatalog::class;

    protected static ?string $navigationGroup = 'Crop Farming';

    protected static ?string $navigationLabel = 'Catalog';

    protected static ?string $modelLabel = 'Crop';

    protected static ?string $pluralModelLabel = 'Crop Catalog';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'crop-farming/crop-catalog';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view crops') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create crops') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit crops') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete crops') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Crop Identity')
                    ->description('Define crops, varieties, timelines, care routines, and production intelligence.')
                    ->icon('heroicon-o-sparkles')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('crop_code')
                            ->label('Crop Code')
                            ->placeholder('Auto-generated')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('name')
                            ->label('Crop Name')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-sparkles')
                            ->placeholder('Maize, Avocado, Napier Grass')
                            ->columnSpan(5),
                        Forms\Components\TextInput::make('variety')
                            ->label('Variety')
                            ->maxLength(255)
                            ->placeholder('Hass, General, H614D, etc.')
                            ->columnSpan(4),
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->native(false)
                            ->searchable()
                            ->default('general')
                            ->required()
                            ->options([
                                'cereal' => 'Cereal',
                                'fruit_tree' => 'Fruit Tree',
                                'vegetable' => 'Vegetable',
                                'fodder' => 'Fodder',
                                'nursery' => 'Nursery',
                                'legume' => 'Legume',
                                'tuber' => 'Tuber',
                                'herb' => 'Herb',
                                'general' => 'General',
                            ])
                            ->prefixIcon('heroicon-o-squares-2x2')
                            ->columnSpan(4),
                        Forms\Components\Select::make('crop_type')
                            ->label('Crop Type')
                            ->native(false)
                            ->default('annual')
                            ->required()
                            ->options([
                                'annual' => 'Annual',
                                'perennial' => 'Perennial',
                                'nursery' => 'Nursery',
                                'orchard' => 'Orchard',
                            ])
                            ->prefixIcon('heroicon-o-arrow-path-rounded-square')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('scientific_name')
                            ->label('Scientific Name')
                            ->maxLength(255)
                            ->placeholder('Optional')
                            ->columnSpan(4),
                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Crop Image / 3D Visual')
                            ->image()
                            ->imageEditor()
                            ->directory('crops/catalog')
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Growth Calendar')
                    ->description('These values are used to automatically estimate germination, transplanting, and harvest dates.')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('germination_days_min')
                            ->label('Germination Min Days')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('germination_days_max')
                            ->label('Germination Max Days')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('transplant_days')
                            ->label('Transplant Days')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Useful for seedlings/nursery crops.')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('harvest_window_days')
                            ->label('Harvest Window Days')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('maturity_days_min')
                            ->label('Harvest Min Days')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('maturity_days_max')
                            ->label('Harvest Max Days')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(3),
                        Forms\Components\Toggle::make('supports_nursery')
                            ->label('Supports Nursery')
                            ->helperText('Enable for seedlings like avocado, tree nursery, vegetables.')
                            ->columnSpan(3),
                        Forms\Components\Toggle::make('is_perennial')
                            ->label('Perennial Crop')
                            ->helperText('Enable for avocado, napier, trees, pasture.')
                            ->columnSpan(3),
                    ]),
                Forms\Components\Section::make('Spacing, Yield & Agronomy')
                    ->icon('heroicon-o-beaker')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('spacing_between_rows_cm')
                            ->label('Row Spacing CM')
                            ->numeric()
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('spacing_between_plants_cm')
                            ->label('Plant Spacing CM')
                            ->numeric()
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('seed_rate_per_acre')
                            ->label('Seed Rate / Acre')
                            ->numeric()
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('seed_rate_unit')
                            ->label('Seed Rate Unit')
                            ->placeholder('kg, seeds, trays')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('expected_yield_per_acre')
                            ->label('Expected Yield / Acre')
                            ->numeric()
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('yield_unit')
                            ->label('Yield Unit')
                            ->placeholder('kg, bags, crates, tonnes')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('water_requirement')
                            ->label('Water Requirement')
                            ->placeholder('Low, Moderate, High')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('soil_requirement')
                            ->label('Soil Requirement')
                            ->placeholder('Well-drained fertile loam')
                            ->columnSpan(3),
                    ]),
                Forms\Components\Section::make('Care Intelligence')
                    ->description('These notes guide care routines and operational planning.')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Textarea::make('care_routine')
                            ->label('Care Routine')
                            ->rows(4)
                            ->columnSpan(6),
                        Forms\Components\Textarea::make('fertilizer_routine')
                            ->label('Fertilizer Routine')
                            ->rows(4)
                            ->columnSpan(6),
                        Forms\Components\Textarea::make('spray_routine')
                            ->label('Spray / Pest Routine')
                            ->rows(4)
                            ->columnSpan(6),
                        Forms\Components\Textarea::make('harvest_notes')
                            ->label('Harvest Notes')
                            ->rows(4)
                            ->columnSpan(6),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Crop')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->height(48)
                    ->width(48)
                    ->defaultImageUrl(url('/images/crop-placeholder.png')),
                Tables\Columns\TextColumn::make('crop_code')
                    ->label('Code')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Crop')
                    ->searchable(['name', 'variety'])
                    ->sortable(['name'])
                    ->weight('bold')
                    ->description(fn(CropCatalog $record): string =>
                        trim(($record->scientific_name ?: 'Crop') . ' • ' . $record->crop_type_label))
                    ->icon('heroicon-o-sparkles'),
                Tables\Columns\TextColumn::make('category_label')
                    ->label('Category')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('germination_days_min')
                    ->label('Germination')
                    ->formatStateUsing(fn($state, CropCatalog $record): string =>
                        $record->germination_days_min || $record->germination_days_max
                            ? ($record->germination_days_min ?? '-') . ' - ' . ($record->germination_days_max ?? '-') . ' days'
                            : 'N/A')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('maturity_days_min')
                    ->label('Harvest')
                    ->formatStateUsing(fn($state, CropCatalog $record): string =>
                        $record->maturity_days_min || $record->maturity_days_max
                            ? ($record->maturity_days_min ?? '-') . ' - ' . ($record->maturity_days_max ?? '-') . ' days'
                            : 'N/A')
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('supports_nursery')
                    ->label('Nursery')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_perennial')
                    ->label('Perennial')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'cereal' => 'Cereal',
                        'fruit_tree' => 'Fruit Tree',
                        'vegetable' => 'Vegetable',
                        'fodder' => 'Fodder',
                        'nursery' => 'Nursery',
                        'legume' => 'Legume',
                        'tuber' => 'Tuber',
                        'herb' => 'Herb',
                        'general' => 'General',
                    ]),
                Tables\Filters\TernaryFilter::make('supports_nursery')
                    ->label('Supports Nursery'),
                Tables\Filters\TernaryFilter::make('is_perennial')
                    ->label('Perennial'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(): bool => auth()->user()?->can('delete crops') || auth()->user()?->hasRole('Administrator')),
            ])
            ->emptyStateIcon('heroicon-o-sparkles')
            ->emptyStateHeading('No crops defined')
            ->emptyStateDescription('Add maize, avocado, seedlings, napier, vegetables, and other crops.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCropCatalogs::route('/'),
            'create' => Pages\CreateCropCatalog::route('/create'),
            'edit' => Pages\EditCropCatalog::route('/{record}/edit'),
        ];
    }
}
