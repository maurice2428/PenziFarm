<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropInputApplicationResource\Pages;
use App\Models\CropInputApplication;
use App\Models\CropSeason;
use App\Models\FieldPartition;
use App\Models\InventoryItem;
use App\Models\NurseryBatch;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class CropInputApplicationResource extends Resource
{
    protected static ?string $model = CropInputApplication::class;

    protected static ?string $navigationGroup = 'Crop Farming';

    protected static ?string $navigationLabel = 'Input Applications';

    protected static ?string $modelLabel = 'Crop Input Application';

    protected static ?string $pluralModelLabel = 'Crop Input Applications';

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'crop-farming/input-applications';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view crop input applications') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create crop input applications') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Application Target')
                    ->description('Apply seeds, fertiliser, chemicals, manure, nursery media, or other inputs. Stock reduces automatically.')
                    ->icon('heroicon-o-beaker')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('application_no')
                            ->label('Application No.')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('application_date')
                            ->label('Application Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->columnSpan(3),
                        Forms\Components\Select::make('crop_season_id')
                            ->label('Crop Season')
                            ->options(fn(): array => CropSeason::query()
                                ->with('cropCatalog')
                                ->latest()
                                ->get()
                                ->mapWithKeys(fn(CropSeason $season): array => [
                                    $season->id => $season->name . ' • ' . $season->crop_name,
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (?int $state, Set $set): void {
                                $season = CropSeason::query()->find($state);

                                if ($season) {
                                    $set('field_partition_id', $season->field_partition_id);
                                    $set('target_area', $season->area_planted);
                                    $set('area_unit', $season->area_unit);
                                }
                            })
                            ->prefixIcon('heroicon-o-sun')
                            ->columnSpan(6),
                        Forms\Components\Select::make('nursery_batch_id')
                            ->label('Nursery Batch')
                            ->options(fn(): array => NurseryBatch::query()
                                ->with('cropCatalog')
                                ->latest()
                                ->get()
                                ->mapWithKeys(fn(NurseryBatch $batch): array => [
                                    $batch->id => $batch->name . ' • ' . $batch->crop_name,
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-beaker')
                            ->columnSpan(6),
                        Forms\Components\Select::make('field_partition_id')
                            ->label('Field Partition')
                            ->options(fn(): array => FieldPartition::query()
                                ->with('farmField')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn(FieldPartition $partition): array => [
                                    $partition->id => ($partition->farmField?->name ?? 'Field') . ' • ' . $partition->name,
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-squares-2x2')
                            ->columnSpan(6),
                        Forms\Components\Select::make('application_type')
                            ->label('Application Type')
                            ->native(false)
                            ->required()
                            ->options([
                                'seed' => 'Seed',
                                'fertilizer' => 'Fertilizer',
                                'chemical' => 'Chemical',
                                'manure' => 'Manure',
                                'irrigation' => 'Irrigation',
                                'nursery_media' => 'Nursery Media',
                                'other' => 'Other',
                            ])
                            ->prefixIcon('heroicon-o-squares-2x2')
                            ->columnSpan(6),
                    ]),
                Forms\Components\Section::make('Inventory Issue')
                    ->description('Selected input will be deducted from stock and logged in stock movements as crop_input.')
                    ->icon('heroicon-o-cube')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('inventory_item_id')
                            ->label('Inventory Item')
                            ->options(fn(): array => InventoryItem::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn(InventoryItem $item): array => [
                                    $item->id => $item->name . ' • Stock: ' . number_format((float) $item->current_stock, 3) . ' ' . $item->unit,
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?int $state, Set $set): void {
                                $item = InventoryItem::query()->find($state);

                                if (!$item) {
                                    return;
                                }

                                $set('unit', $item->unit);
                                $set('unit_cost', number_format((float) $item->unit_cost, 2, '.', ''));
                                $set('available_stock', number_format((float) $item->current_stock, 3, '.', ''));
                            })
                            ->prefixIcon('heroicon-o-cube')
                            ->columnSpan(6),
                        Forms\Components\TextInput::make('available_stock')
                            ->label('Available Stock')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity Used')
                            ->numeric()
                            ->minValue(0.001)
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('unit')
                            ->label('Unit')
                            ->readOnly()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Unit Cost')
                            ->prefix('KES')
                            ->numeric()
                            ->required()
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('target_area')
                            ->label('Target Area')
                            ->numeric()
                            ->columnSpan(3),
                        Forms\Components\Select::make('area_unit')
                            ->label('Area Unit')
                            ->native(false)
                            ->options([
                                'acre' => 'Acre',
                                'hectare' => 'Hectare',
                                'sqm' => 'Square Metres',
                            ])
                            ->columnSpan(3),
                        Forms\Components\Select::make('method')
                            ->label('Application Method')
                            ->native(false)
                            ->options([
                                'broadcast' => 'Broadcast',
                                'drip' => 'Drip',
                                'foliar' => 'Foliar',
                                'soil_drench' => 'Soil Drench',
                                'spray' => 'Spray',
                                'manual' => 'Manual',
                                'other' => 'Other',
                            ])
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('applied_by')
                            ->label('Applied By')
                            ->maxLength(255)
                            ->columnSpan(6),
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
            ->defaultSort('application_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('application_no')
                    ->label('No.')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('application_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('application_type_label')
                    ->label('Type')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('inventoryItem.name')
                    ->label('Input')
                    ->searchable()
                    ->weight('bold')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('cropSeason.name')
                    ->label('Season')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('nurseryBatch.name')
                    ->label('Nursery')
                    ->searchable()
                    ->placeholder('N/A')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->formatStateUsing(fn($state, CropInputApplication $record): string =>
                        number_format((float) $state, 3) . ' ' . ($record->unit ?: ''))
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Cost')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('applied_by')
                    ->label('Applied By')
                    ->placeholder('N/A')
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl'),
            ])
            ->emptyStateIcon('heroicon-o-beaker')
            ->emptyStateHeading('No crop input applications')
            ->emptyStateDescription('Record fertiliser, seed, chemical, nursery media, and manure usage from inventory.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCropInputApplications::route('/'),
            'create' => Pages\CreateCropInputApplication::route('/create'),
        ];
    }
}
