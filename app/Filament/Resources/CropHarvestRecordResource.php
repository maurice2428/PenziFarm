<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropHarvestRecordResource\Pages;
use App\Models\CropHarvestRecord;
use App\Models\CropSeason;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class CropHarvestRecordResource extends Resource
{
    protected static ?string $model = CropHarvestRecord::class;

    protected static ?string $navigationGroup = 'Crop Farming';

    protected static ?string $navigationLabel = 'Harvest Records';

    protected static ?string $modelLabel = 'Harvest Record';

    protected static ?string $pluralModelLabel = 'Harvest Records';

    protected static ?string $navigationIcon = 'heroicon-o-gift-top';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'crop-farming/harvest-records';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view crop harvests') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create crop harvests') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit crop harvests') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Harvest Details')
                    ->description('Capture crop output, grading, rejection/losses, value, and harvest notes.')
                    ->icon('heroicon-o-gift-top')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('harvest_no')
                            ->label('Harvest No.')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
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
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?int $state, Set $set): void {
                                $season = CropSeason::query()->with('cropCatalog')->find($state);

                                if ($season?->cropCatalog?->yield_unit) {
                                    $set('unit', $season->cropCatalog->yield_unit);
                                }
                            })
                            ->prefixIcon('heroicon-o-sun')
                            ->columnSpan(6),
                        Forms\Components\DatePicker::make('harvest_date')
                            ->label('Harvest Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Total Quantity')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('unit')
                            ->label('Unit')
                            ->default('kg')
                            ->required()
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('unit_value')
                            ->label('Unit Value')
                            ->prefix('KES')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('estimated_value')
                            ->label('Estimated Value')
                            ->prefix('KES')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('grade_a_quantity')
                            ->label('Grade A')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('grade_b_quantity')
                            ->label('Grade B')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('rejected_quantity')
                            ->label('Rejected / Loss')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('harvested_by')
                            ->label('Harvested By')
                            ->maxLength(255)
                            ->columnSpan(3),
                        Forms\Components\Textarea::make('notes')
                            ->label('Harvest Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('harvest_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('harvest_no')
                    ->label('No.')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('harvest_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cropSeason.name')
                    ->label('Season')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn(CropHarvestRecord $record): string =>
                        $record->cropSeason?->crop_name ?? 'N/A'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->formatStateUsing(fn($state, CropHarvestRecord $record): string =>
                        number_format((float) $state, 3) . ' ' . ($record->unit ?: ''))
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('grade_a_quantity')
                    ->label('Grade A')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('grade_b_quantity')
                    ->label('Grade B')
                    ->numeric()
                    ->badge()
                    ->color('warning')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rejected_quantity')
                    ->label('Rejected')
                    ->numeric()
                    ->badge()
                    ->color('danger')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('estimated_value')
                    ->label('Value')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('harvested_by')
                    ->label('Harvested By')
                    ->placeholder('N/A')
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl'),
                Tables\Actions\EditAction::make(),
            ])
            ->emptyStateIcon('heroicon-o-gift-top')
            ->emptyStateHeading('No harvest records')
            ->emptyStateDescription('Record harvests, grades, rejected quantity, and estimated value.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCropHarvestRecords::route('/'),
            'create' => Pages\CreateCropHarvestRecord::route('/create'),
            'edit' => Pages\EditCropHarvestRecord::route('/{record}/edit'),
        ];
    }
}
