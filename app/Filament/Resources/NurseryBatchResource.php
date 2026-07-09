<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NurseryBatchResource\Pages;
use App\Models\CropCatalog;
use App\Models\FarmField;
use App\Models\FieldPartition;
use App\Models\NurseryBatch;
use App\Services\Crops\CropCalendarService;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class NurseryBatchResource extends Resource
{
    protected static ?string $model = NurseryBatch::class;

    protected static ?string $navigationGroup = 'Crop Farming';

    protected static ?string $navigationLabel = 'Nursery Batches';

    protected static ?string $modelLabel = 'Nursery Batch';

    protected static ?string $pluralModelLabel = 'Nursery Batches';

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'crop-farming/nursery-batches';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view nursery batches') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create nursery batches') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit nursery batches') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Nursery Batch')
                    ->description('Track seedling batches, germination, losses, healthy seedlings, and transplant readiness.')
                    ->icon('heroicon-o-beaker')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('batch_code')
                            ->label('Batch Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('name')
                            ->label('Batch Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Hass Avocado Nursery Batch 01')
                            ->prefixIcon('heroicon-o-beaker')
                            ->columnSpan(5),
                        Forms\Components\Select::make('crop_catalog_id')
                            ->label('Crop / Seedling')
                            ->options(fn(): array => CropCatalog::query()
                                ->where('is_active', true)
                                ->where(function ($query) {
                                    $query
                                        ->where('supports_nursery', true)
                                        ->orWhere('crop_type', 'nursery')
                                        ->orWhere('category', 'nursery');
                                })
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
                                static::applyNurseryCalendar($state, $get('sowing_date'), $set))
                            ->prefixIcon('heroicon-o-sparkles')
                            ->columnSpan(4),
                        Forms\Components\Select::make('farm_field_id')
                            ->label('Nursery Field')
                            ->options(fn(): array => FarmField::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('field_partition_id', null))
                            ->prefixIcon('heroicon-o-map')
                            ->columnSpan(4),
                        Forms\Components\Select::make('field_partition_id')
                            ->label('Nursery Bed / Partition')
                            ->options(fn(Get $get): array => FieldPartition::query()
                                ->when($get('farm_field_id'), fn($query, $fieldId) => $query->where('farm_field_id', $fieldId))
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn(FieldPartition $partition): array => [
                                    $partition->id => $partition->name . ' • ' . number_format((float) $partition->area, 3) . ' ' . $partition->area_unit,
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-squares-2x2')
                            ->columnSpan(4),
                        Forms\Components\DatePicker::make('sowing_date')
                            ->label('Sowing Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($state, Get $get, Set $set) =>
                                static::applyNurseryCalendar($get('crop_catalog_id'), $state, $set))
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('seed_quantity')
                            ->label('Seed Quantity')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('seed_unit')
                            ->label('Seed Unit')
                            ->placeholder('kg, seeds, trays')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('initial_seedlings')
                            ->label('Initial Seedlings')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(3),
                    ]),
                Forms\Components\Section::make('Germination & Transplant Calendar')
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
                        Forms\Components\DatePicker::make('actual_germination_date')
                            ->label('Actual Germination')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('expected_transplant_date')
                            ->label('Expected Transplant')
                            ->native(false)
                            ->columnSpan(3),
                    ]),
                Forms\Components\Section::make('Seedling Status')
                    ->icon('heroicon-o-chart-bar-square')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('germinated_seedlings')
                            ->label('Germinated')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('healthy_seedlings')
                            ->label('Healthy')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('weak_seedlings')
                            ->label('Weak')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('dead_seedlings')
                            ->label('Dead')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('transplanted_seedlings')
                            ->label('Transplanted')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('germination_percent')
                            ->label('Germination %')
                            ->numeric()
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(2),
                        Forms\Components\Select::make('growth_stage')
                            ->label('Growth Stage')
                            ->native(false)
                            ->default('sown')
                            ->options([
                                'sown' => 'Sown',
                                'germinating' => 'Germinating',
                                'emerged' => 'Emerged',
                                'hardening' => 'Hardening',
                                'ready_to_transplant' => 'Ready To Transplant',
                                'transplanted' => 'Transplanted',
                                'closed' => 'Closed',
                            ])
                            ->columnSpan(4),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->native(false)
                            ->default('active')
                            ->options([
                                'active' => 'Active',
                                'ready' => 'Ready',
                                'transplanted' => 'Transplanted',
                                'failed' => 'Failed',
                                'closed' => 'Closed',
                            ])
                            ->columnSpan(4),
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
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('batch_code')
                    ->label('Batch')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nursery Batch')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(NurseryBatch $record): string =>
                        $record->crop_name . ' • Available: ' . number_format($record->available_seedlings))
                    ->icon('heroicon-o-beaker'),
                Tables\Columns\TextColumn::make('sowing_date')
                    ->label('Sown')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expected_transplant_date')
                    ->label('Transplant ETA')
                    ->date('d M Y')
                    ->badge()
                    ->color('warning')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('initial_seedlings')
                    ->label('Initial')
                    ->numeric()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('healthy_seedlings')
                    ->label('Healthy')
                    ->numeric()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('weak_seedlings')
                    ->label('Weak')
                    ->numeric()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('dead_seedlings')
                    ->label('Dead')
                    ->numeric()
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('germination_percent')
                    ->label('Germination')
                    ->suffix('%')
                    ->badge()
                    ->color('info')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('growth_stage')
                    ->label('Stage')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state): string => str($state)->replace('_', ' ')->title()),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state): string => match ($state) {
                        'active' => 'success',
                        'ready' => 'warning',
                        'transplanted' => 'success',
                        'failed' => 'danger',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state): string => str($state)->replace('_', ' ')->title()),
            ])
            ->actions([
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
                        Forms\Components\TextInput::make('germinated_seedlings')
                            ->label('Germinated Seedlings')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('healthy_seedlings')
                            ->label('Healthy Seedlings')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('weak_seedlings')
                            ->label('Weak Seedlings')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('dead_seedlings')
                            ->label('Dead Seedlings')
                            ->numeric()
                            ->default(0),
                    ])
                    ->action(function (NurseryBatch $record, array $data): void {
                        $record->forceFill([
                            'actual_germination_date' => $data['actual_germination_date'],
                            'germinated_seedlings' => $data['germinated_seedlings'],
                            'healthy_seedlings' => $data['healthy_seedlings'],
                            'weak_seedlings' => $data['weak_seedlings'] ?? 0,
                            'dead_seedlings' => $data['dead_seedlings'] ?? 0,
                            'growth_stage' => 'emerged',
                        ])->save();

                        Notification::make()
                            ->title('Nursery germination recorded')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl'),
                Tables\Actions\EditAction::make(),
            ])
            ->emptyStateIcon('heroicon-o-beaker')
            ->emptyStateHeading('No nursery batches')
            ->emptyStateDescription('Create nursery batches for avocado seedlings, tree seedlings, vegetables, and other nursery crops.');
    }

    public static function applyNurseryCalendar(?int $cropId, ?string $baseDate, Set $set): void
    {
        if (!$cropId || !$baseDate) {
            return;
        }

        $crop = CropCatalog::query()->find($cropId);

        if (!$crop) {
            return;
        }

        $dates = app(CropCalendarService::class)->datesFor($crop, $baseDate);

        foreach (['expected_germination_from', 'expected_germination_to', 'expected_transplant_date'] as $field) {
            $set($field, $dates[$field] ?? null);
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNurseryBatches::route('/'),
            'create' => Pages\CreateNurseryBatch::route('/create'),
            'edit' => Pages\EditNurseryBatch::route('/{record}/edit'),
        ];
    }
}
