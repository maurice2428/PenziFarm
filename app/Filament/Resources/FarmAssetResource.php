<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FarmAssetResource\Pages;
use App\Models\AssetMaintenanceRecord;
use App\Models\AssetValuation;
use App\Models\FarmAsset;
use App\Models\Location;
use App\Models\Supplier;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FarmAssetResource extends Resource
{
    protected static ?string $model = FarmAsset::class;

    protected static ?string $navigationGroup = 'Asset Valuation';

    protected static ?string $navigationLabel = 'Asset Register';

    protected static ?string $modelLabel = 'Asset';

    protected static ?string $pluralModelLabel = 'Asset Register';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'assets/register';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view assets') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create assets') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit assets') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete assets') ||
            auth()->user()?->hasRole('Admin') ||
            auth()->user()?->hasRole('Administrator') ||
            false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12)
            ->schema([
                Forms\Components\Section::make('Asset Identity')
                    ->description('Register machinery, tractors, vehicles, equipment, buildings, infrastructure, electronics, and other farm assets.')
                    ->icon('heroicon-o-building-office-2')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('asset_number')
                            ->label('Asset No.')
                            ->placeholder('Auto-generated')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('name')
                            ->label('Asset Name')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-cube')
                            ->placeholder('e.g. Massey Ferguson Tractor, CCTV NVR, Water Pump')
                            ->columnSpan(5),
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->native(false)
                            ->searchable()
                            ->required()
                            ->options([
                                'land' => 'Land',
                                'building' => 'Building / Structure',
                                'vehicle' => 'Vehicle',
                                'machinery' => 'Machinery',
                                'equipment' => 'Equipment',
                                'furniture' => 'Furniture',
                                'ict' => 'ICT / Electronics',
                                'livestock_infrastructure' => 'Livestock Infrastructure',
                                'crop_infrastructure' => 'Crop Infrastructure',
                                'water_system' => 'Water System',
                                'power_system' => 'Power System',
                                'general' => 'General Asset',
                            ])
                            ->prefixIcon('heroicon-o-squares-2x2')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('asset_type')
                            ->label('Asset Type')
                            ->placeholder('e.g. Tractor, Borehole, CCTV, Crush, Shed')
                            ->maxLength(255)
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('tag_number')
                            ->label('Tag Number')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-tag')
                            ->placeholder('Internal asset tag')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('serial_number')
                            ->label('Serial / Chassis / Engine No.')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-finger-print')
                            ->placeholder('Serial, chassis, engine, or IMEI number')
                            ->columnSpan(4),
                        static::locationSelect()
                            ->columnSpan(6),
                        static::supplierSelect()
                            ->columnSpan(6),
                    ]),
                Forms\Components\Section::make('Valuation & Aging')
                    ->description('Track purchase cost, current value, depreciation, useful life, and valuation schedule.')
                    ->icon('heroicon-o-banknotes')
                    ->columns(12)
                    ->schema([
                        Forms\Components\DatePicker::make('acquisition_date')
                            ->label('Acquisition Date')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('purchase_cost')
                            ->label('Purchase Cost')
                            ->prefix('KES')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('current_value')
                            ->label('Current Value')
                            ->prefix('KES')
                            ->numeric()
                            ->default(0)
                            ->helperText('Updated manually or through valuation records.')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('salvage_value')
                            ->label('Salvage Value')
                            ->prefix('KES')
                            ->numeric()
                            ->default(0)
                            ->helperText('Expected value at the end of useful life.')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('useful_life_months')
                            ->label('Useful Life / Months')
                            ->numeric()
                            ->default(60)
                            ->minValue(1)
                            ->required()
                            ->helperText('Example: tractor 96 months, pump 60 months, laptop 36 months.')
                            ->columnSpan(3),
                        Forms\Components\Select::make('depreciation_method')
                            ->label('Depreciation Method')
                            ->native(false)
                            ->default('straight_line')
                            ->required()
                            ->options([
                                'straight_line' => 'Straight Line',
                                'manual_valuation' => 'Manual Valuation',
                            ])
                            ->helperText('Straight Line spreads value loss evenly. Manual Valuation uses actual valuation/current value.')
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('last_valuation_date')
                            ->label('Last Valuation')
                            ->native(false)
                            ->columnSpan(3),
                        Forms\Components\DatePicker::make('next_valuation_date')
                            ->label('Next Valuation')
                            ->native(false)
                            ->columnSpan(3),
                    ]),
                Forms\Components\Section::make('Condition & Status')
                    ->description('Monitor the current physical and operational status of the asset.')
                    ->icon('heroicon-o-shield-check')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('condition')
                            ->label('Condition')
                            ->native(false)
                            ->default('good')
                            ->required()
                            ->options([
                                'excellent' => 'Excellent',
                                'good' => 'Good',
                                'fair' => 'Fair',
                                'poor' => 'Poor',
                                'damaged' => 'Damaged',
                                'disposed' => 'Disposed',
                            ])
                            ->columnSpan(4),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->native(false)
                            ->default('active')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'under_maintenance' => 'Under Maintenance',
                                'idle' => 'Idle',
                                'disposed' => 'Disposed',
                                'lost' => 'Lost',
                            ])
                            ->columnSpan(4),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function locationSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('location_id')
            ->label('Location')
            ->options(fn(): array => static::locationOptions())
            ->searchable()
            ->preload()
            ->prefixIcon('heroicon-o-map-pin')
            ->placeholder('Select location')
            ->helperText('Use + to add a location without leaving this page.')
            ->suffixActions([
                FormAction::make('addLocation')
                    ->icon('heroicon-o-plus-circle')
                    ->tooltip('Add Location')
                    ->color('success')
                    ->slideOver()
                    ->modalWidth('lg')
                    ->modalHeading('Add Location')
                    ->modalDescription('Create a new farm location without leaving this asset form.')
                    ->modalSubmitActionLabel('Save Location')
                    ->form(static::locationCreateForm())
                    ->action(function (array $data, Set $set): void {
                        $location = static::createLocationFromForm($data);

                        $set('location_id', $location->id);

                        Notification::make()
                            ->title('Location added')
                            ->body('The new location has been selected for this asset.')
                            ->success()
                            ->send();
                    }),
                FormAction::make('refreshLocations')
                    ->icon('heroicon-o-arrow-path')
                    ->tooltip('Refresh Locations')
                    ->color('gray')
                    ->action(function (): void {
                        Notification::make()
                            ->title('Locations refreshed')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function supplierSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('supplier_id')
            ->label('Supplier / Vendor')
            ->options(fn(): array => static::supplierOptions())
            ->searchable()
            ->preload()
            ->prefixIcon('heroicon-o-building-storefront')
            ->placeholder('Select supplier')
            ->helperText('Use + to add a supplier without leaving this page.')
            ->suffixActions([
                FormAction::make('addSupplier')
                    ->icon('heroicon-o-plus-circle')
                    ->tooltip('Add Supplier')
                    ->color('success')
                    ->slideOver()
                    ->modalWidth('lg')
                    ->modalHeading('Add Supplier')
                    ->modalDescription('Create a new supplier or vendor without leaving this asset form.')
                    ->modalSubmitActionLabel('Save Supplier')
                    ->form(static::supplierCreateForm())
                    ->action(function (array $data, Set $set): void {
                        $supplier = static::createSupplierFromForm($data);

                        $set('supplier_id', $supplier->id);

                        Notification::make()
                            ->title('Supplier added')
                            ->body('The new supplier has been selected for this asset.')
                            ->success()
                            ->send();
                    }),
                FormAction::make('refreshSuppliers')
                    ->icon('heroicon-o-arrow-path')
                    ->tooltip('Refresh Suppliers')
                    ->color('gray')
                    ->action(function (): void {
                        Notification::make()
                            ->title('Suppliers refreshed')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('asset_number')
                    ->label('Asset No.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Asset')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(FarmAsset $record): string =>
                        trim(($record->asset_type ?: 'Asset') . ' • ' . ($record->tag_number ?: 'No Tag')))
                    ->icon('heroicon-o-cube'),
                Tables\Columns\TextColumn::make('category_label')
                    ->label('Category')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('location_display')
                    ->label('Location')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('supplier_display')
                    ->label('Supplier')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('acquisition_date')
                    ->label('Acquired')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('age_display')
                    ->label('Age')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('purchase_cost')
                    ->label('Cost')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_value')
                    ->label('Current Value')
                    ->money('KES')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('estimated_book_value')
                    ->label('Book Value')
                    ->money('KES'),
                Tables\Columns\TextColumn::make('depreciation_to_date')
                    ->label('Depreciation')
                    ->money('KES')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('depreciation_method_label')
                    ->label('Method')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('aging_status')
                    ->label('Aging')
                    ->badge()
                    ->color(fn(FarmAsset $record): string => match ($record->aging_status) {
                        'Healthy Life' => 'success',
                        'Aging Soon' => 'warning',
                        'Near End of Life' => 'danger',
                        'Fully Aged' => 'danger',
                        'Disposed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('condition_label')
                    ->label('Condition')
                    ->badge()
                    ->color(fn(FarmAsset $record): string => match ($record->condition) {
                        'excellent' => 'success',
                        'good' => 'success',
                        'fair' => 'warning',
                        'poor' => 'danger',
                        'damaged' => 'danger',
                        'disposed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn(FarmAsset $record): string => match ($record->status) {
                        'active' => 'success',
                        'under_maintenance' => 'warning',
                        'idle' => 'gray',
                        'disposed' => 'danger',
                        'lost' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('next_valuation_status')
                    ->label('Valuation')
                    ->badge()
                    ->color(fn(FarmAsset $record): string => match ($record->next_valuation_status) {
                        'Scheduled' => 'success',
                        'Due Soon' => 'warning',
                        'Overdue' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'land' => 'Land',
                        'building' => 'Building / Structure',
                        'vehicle' => 'Vehicle',
                        'machinery' => 'Machinery',
                        'equipment' => 'Equipment',
                        'furniture' => 'Furniture',
                        'ict' => 'ICT / Electronics',
                        'livestock_infrastructure' => 'Livestock Infrastructure',
                        'crop_infrastructure' => 'Crop Infrastructure',
                        'water_system' => 'Water System',
                        'power_system' => 'Power System',
                        'general' => 'General Asset',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'under_maintenance' => 'Under Maintenance',
                        'idle' => 'Idle',
                        'disposed' => 'Disposed',
                        'lost' => 'Lost',
                    ]),
                Tables\Filters\SelectFilter::make('condition')
                    ->options([
                        'excellent' => 'Excellent',
                        'good' => 'Good',
                        'fair' => 'Fair',
                        'poor' => 'Poor',
                        'damaged' => 'Damaged',
                        'disposed' => 'Disposed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('recordValuation')
                    ->label('Valuation')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->slideOver()
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\DatePicker::make('valuation_date')
                            ->label('Valuation Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required(),
                        Forms\Components\Select::make('valuation_type')
                            ->label('Valuation Type')
                            ->native(false)
                            ->default('revaluation')
                            ->required()
                            ->options([
                                'purchase' => 'Purchase Value',
                                'revaluation' => 'Revaluation',
                                'impairment' => 'Impairment',
                                'disposal_estimate' => 'Disposal Estimate',
                                'insurance' => 'Insurance Valuation',
                            ]),
                        Forms\Components\TextInput::make('valuation_amount')
                            ->label('New Value')
                            ->prefix('KES')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('condition')
                            ->label('Condition')
                            ->native(false)
                            ->options([
                                'excellent' => 'Excellent',
                                'good' => 'Good',
                                'fair' => 'Fair',
                                'poor' => 'Poor',
                                'damaged' => 'Damaged',
                                'disposed' => 'Disposed',
                            ]),
                        Forms\Components\TextInput::make('valuer_name')
                            ->label('Valuer / Officer'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Valuation Notes')
                            ->rows(3),
                    ])
                    ->action(function (FarmAsset $record, array $data): void {
                        $previousValue = (float) $record->current_value;
                        $newValue = (float) $data['valuation_amount'];

                        AssetValuation::query()->create([
                            'farm_asset_id' => $record->id,
                            'valuation_date' => $data['valuation_date'],
                            'valuation_type' => $data['valuation_type'],
                            'previous_value' => $previousValue,
                            'valuation_amount' => $newValue,
                            'depreciation_amount' => max(0, $previousValue - $newValue),
                            'condition' => $data['condition'] ?? $record->condition,
                            'valuer_name' => $data['valuer_name'] ?? null,
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Asset valuation recorded')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('recordMaintenance')
                    ->label('Maintenance')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('warning')
                    ->slideOver()
                    ->modalWidth('lg')
                    ->form([
                        Forms\Components\DatePicker::make('maintenance_date')
                            ->label('Maintenance Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required(),
                        Forms\Components\Select::make('maintenance_type')
                            ->label('Type')
                            ->native(false)
                            ->default('routine')
                            ->required()
                            ->options([
                                'routine' => 'Routine',
                                'repair' => 'Repair',
                                'service' => 'Service',
                                'inspection' => 'Inspection',
                                'replacement' => 'Replacement',
                            ]),
                        Forms\Components\TextInput::make('cost')
                            ->label('Cost')
                            ->prefix('KES')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('performed_by')
                            ->label('Performed By'),
                        Forms\Components\DatePicker::make('next_service_date')
                            ->label('Next Service Date')
                            ->native(false),
                        Forms\Components\Textarea::make('notes')
                            ->label('Maintenance Notes')
                            ->rows(3),
                    ])
                    ->action(function (FarmAsset $record, array $data): void {
                        AssetMaintenanceRecord::query()->create([
                            'farm_asset_id' => $record->id,
                            'maintenance_date' => $data['maintenance_date'],
                            'maintenance_type' => $data['maintenance_type'],
                            'cost' => $data['cost'] ?? 0,
                            'performed_by' => $data['performed_by'] ?? null,
                            'next_service_date' => $data['next_service_date'] ?? null,
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]);

                        $record->forceFill([
                            'status' => 'under_maintenance',
                        ])->saveQuietly();

                        Notification::make()
                            ->title('Maintenance record saved')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make()
                    ->modalWidth('7xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(): bool =>
                        auth()->user()?->can('delete assets') ||
                        auth()->user()?->hasRole('Admin') ||
                        auth()->user()?->hasRole('Administrator')),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-building-office-2')
            ->emptyStateHeading('No assets registered')
            ->emptyStateDescription('Register vehicles, equipment, buildings, machinery, infrastructure, electronics, and other farm assets.');
    }

    public static function locationCreateForm(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Location Name')
                ->required()
                ->maxLength(255)
                ->prefixIcon('heroicon-o-map-pin')
                ->placeholder('e.g. Main Store, Machinery Yard, Dhiwa Farm, Munetho'),
            Forms\Components\Select::make('type')
                ->label('Location Type')
                ->native(false)
                ->default('farm')
                ->required(fn(): bool => Schema::hasColumn('locations', 'type'))
                ->visible(fn(): bool => Schema::hasColumn('locations', 'type'))
                ->options([
                    'farm' => 'Farm',
                    'store' => 'Store',
                    'yard' => 'Yard',
                    'paddock' => 'Paddock',
                    'office' => 'Office',
                    'field' => 'Field',
                    'warehouse' => 'Warehouse',
                    'machinery_yard' => 'Machinery Yard',
                    'other' => 'Other',
                ])
                ->prefixIcon('heroicon-o-squares-2x2'),
            Forms\Components\Textarea::make('description')
                ->label('Description / Notes')
                ->rows(3)
                ->visible(fn(): bool => Schema::hasColumn('locations', 'description')),
        ];
    }

    public static function supplierCreateForm(): array
    {
        return [
            Forms\Components\TextInput::make('company_name')
                ->label('Supplier Name')
                ->required()
                ->maxLength(255)
                ->prefixIcon('heroicon-o-building-storefront'),
            Forms\Components\Select::make('type')
                ->label('Supplier Type')
                ->native(false)
                ->default('asset_vendor')
                ->visible(fn(): bool =>
                    Schema::hasColumn('suppliers', 'type') ||
                    Schema::hasColumn('suppliers', 'supplier_type'))
                ->required(fn(): bool =>
                    Schema::hasColumn('suppliers', 'type') ||
                    Schema::hasColumn('suppliers', 'supplier_type'))
                ->options([
                    'general' => 'General Supplier',
                    'asset_vendor' => 'Asset Vendor',
                    'inputs_supplier' => 'Inputs Supplier',
                    'service_provider' => 'Service Provider',
                    'contractor' => 'Contractor',
                    'machinery_vendor' => 'Machinery Vendor',
                    'vehicle_vendor' => 'Vehicle Vendor',
                    'other' => 'Other',
                ])
                ->prefixIcon('heroicon-o-squares-2x2'),
            Forms\Components\TextInput::make('contact_person')
                ->label('Contact Person')
                ->maxLength(255)
                ->prefixIcon('heroicon-o-user'),
            Forms\Components\TextInput::make('phone')
                ->label('Phone')
                ->tel()
                ->maxLength(255)
                ->prefixIcon('heroicon-o-phone'),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255)
                ->prefixIcon('heroicon-o-envelope'),
            Forms\Components\TextInput::make('kra_pin')
                ->label('KRA PIN')
                ->maxLength(255)
                ->prefixIcon('heroicon-o-identification'),
            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->rows(3)
                ->visible(fn(): bool => Schema::hasColumn('suppliers', 'notes')),
        ];
    }

    public static function createLocationFromForm(array $data): Location
    {
        if (!Schema::hasTable('locations')) {
            throw ValidationException::withMessages([
                'name' => 'The locations table does not exist.',
            ]);
        }

        $nameColumn = static::locationNameColumn();

        if (!$nameColumn) {
            throw ValidationException::withMessages([
                'name' => 'No supported location name column found. Expected name or location_name.',
            ]);
        }

        $payload = [
            $nameColumn => $data['name'],
        ];

        if (Schema::hasColumn('locations', 'type')) {
            $payload['type'] = $data['type'] ?? 'farm';
        }

        if (Schema::hasColumn('locations', 'description')) {
            $payload['description'] = $data['description'] ?? null;
        }

        if (Schema::hasColumn('locations', 'is_active')) {
            $payload['is_active'] = true;
        }

        if (Schema::hasColumn('locations', 'status')) {
            $payload['status'] = 'active';
        }

        if (Schema::hasColumn('locations', 'created_by') && auth()->check()) {
            $payload['created_by'] = auth()->id();
        }

        $location = new Location();
        $location->forceFill($payload);
        $location->save();

        return $location;
    }

    public static function createSupplierFromForm(array $data): Supplier
    {
        if (!Schema::hasTable('suppliers')) {
            throw ValidationException::withMessages([
                'company_name' => 'The suppliers table does not exist.',
            ]);
        }

        $nameColumn = static::supplierNameColumn();

        if (!$nameColumn) {
            throw ValidationException::withMessages([
                'company_name' => 'No supported supplier name column found. Expected company_name or name.',
            ]);
        }

        $payload = [
            $nameColumn => $data['company_name'],
        ];

        if (Schema::hasColumn('suppliers', 'type')) {
            $payload['type'] = $data['type'] ?? 'asset_vendor';
        }

        if (Schema::hasColumn('suppliers', 'supplier_type')) {
            $payload['supplier_type'] = $data['type'] ?? 'asset_vendor';
        }

        if (Schema::hasColumn('suppliers', 'contact_person')) {
            $payload['contact_person'] = $data['contact_person'] ?? null;
        }

        if (Schema::hasColumn('suppliers', 'phone_primary')) {
            $payload['phone_primary'] = $data['phone'] ?? null;
        } elseif (Schema::hasColumn('suppliers', 'phone')) {
            $payload['phone'] = $data['phone'] ?? null;
        }

        if (Schema::hasColumn('suppliers', 'email')) {
            $payload['email'] = $data['email'] ?? null;
        }

        if (Schema::hasColumn('suppliers', 'kra_pin')) {
            $payload['kra_pin'] = $data['kra_pin'] ?? null;
        }

        if (Schema::hasColumn('suppliers', 'notes')) {
            $payload['notes'] = $data['notes'] ?? null;
        }

        if (Schema::hasColumn('suppliers', 'is_active')) {
            $payload['is_active'] = true;
        }

        if (Schema::hasColumn('suppliers', 'status')) {
            $payload['status'] = 'active';
        }

        if (Schema::hasColumn('suppliers', 'created_by') && auth()->check()) {
            $payload['created_by'] = auth()->id();
        }

        $supplier = new Supplier();
        $supplier->forceFill($payload);
        $supplier->save();

        return $supplier;
    }

    public static function locationNameColumn(): ?string
    {
        if (!Schema::hasTable('locations')) {
            return null;
        }

        if (Schema::hasColumn('locations', 'name')) {
            return 'name';
        }

        if (Schema::hasColumn('locations', 'location_name')) {
            return 'location_name';
        }

        return null;
    }

    public static function supplierNameColumn(): ?string
    {
        if (!Schema::hasTable('suppliers')) {
            return null;
        }

        if (Schema::hasColumn('suppliers', 'company_name')) {
            return 'company_name';
        }

        if (Schema::hasColumn('suppliers', 'name')) {
            return 'name';
        }

        return null;
    }

    public static function locationOptions(): array
    {
        if (!Schema::hasTable('locations')) {
            return [];
        }

        $nameColumn = static::locationNameColumn();

        if (!$nameColumn) {
            return [];
        }

        return Location::query()
            ->orderBy($nameColumn)
            ->get()
            ->mapWithKeys(fn(Location $location): array => [
                $location->id => $location->{$nameColumn} ?? ('Location #' . $location->id),
            ])
            ->toArray();
    }

    public static function supplierOptions(): array
    {
        if (!Schema::hasTable('suppliers')) {
            return [];
        }

        $nameColumn = static::supplierNameColumn();

        if (!$nameColumn) {
            return [];
        }

        return Supplier::query()
            ->orderBy($nameColumn)
            ->get()
            ->mapWithKeys(fn(Supplier $supplier): array => [
                $supplier->id => $supplier->{$nameColumn} ?? ('Supplier #' . $supplier->id),
            ])
            ->toArray();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFarmAssets::route('/'),
            'create' => Pages\CreateFarmAsset::route('/create'),
            'edit' => Pages\EditFarmAsset::route('/{record}/edit'),
        ];
    }
}
