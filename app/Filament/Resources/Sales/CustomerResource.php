<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\CustomerResource\Pages;
use App\Models\Sales\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view customers') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create customers') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit customers') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete customers') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete customers') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore customers') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore customers') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete customers') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete customers') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Identity')
                ->icon('heroicon-o-user-group')
                    ->description('Buyer details used for invoices, receipts, payments, and sales history.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('customer_number')
                            ->label('Customer Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto generated'),
                        Forms\Components\TextInput::make('name')
                            ->label('Customer Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Example: John Mwangi'),
                        Forms\Components\Select::make('customer_type')
                            ->label('Customer Type')
                            ->required()
                            ->options(self::customerTypeOptions())
                            ->default('individual')
                            ->searchable(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(30)
                            ->placeholder('Example: 254712345678'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('Example: customer@email.com'),
                        Forms\Components\TextInput::make('kra_pin')
                            ->label('KRA PIN')
                            ->maxLength(50)
                            ->placeholder('Example: A123456789X'),
                        Forms\Components\TextInput::make('id_number')
                            ->label('ID / Registration Number')
                            ->maxLength(100)
                            ->placeholder('National ID or company registration number'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Customer')
                            ->default(true),
                    ]),
                Forms\Components\Section::make('Location Details')
                ->icon('heroicon-o-map-pin')
                    ->description('Used for customer profiling, invoice records, and future delivery or farm-visit reporting.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('country')
                            ->label('Country')
                            ->default('Kenya')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('county')
                            ->label('County / Region')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('town')
                            ->label('Town / Area')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->readOnly(),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->readOnly(),
                        Forms\Components\Textarea::make('place_label')
                            ->label('Map Place Label')
                            ->rows(2)
                            ->readOnly()
                            ->columnSpanFull(),
                        Forms\Components\View::make('filament.forms.components.customer-location-map')
                            ->label('Customer Map')
                            ->columnSpanFull(),
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
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('customer_number')
                    ->label('Customer No.')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer_type_label')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('kra_pin')
                    ->label('KRA PIN')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('county')
                    ->label('County')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('town')
                    ->label('Town')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_type')
                    ->label('Customer Type')
                    ->options(self::customerTypeOptions()),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn() => auth()->user()?->can('view customers') ?? false),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) =>
                        !$record->trashed() &&
                        (auth()->user()?->can('edit customers') ?? false)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) =>
                        !$record->trashed() &&
                        (auth()->user()?->can('delete customers') ?? false)),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn($record) =>
                        $record->trashed() &&
                        (auth()->user()?->can('restore customers') ?? false)),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn($record) =>
                        $record->trashed() &&
                        (auth()->user()?->can('force delete customers') ?? false)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('printSelected')
                        ->label('Print Selected')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Print selected customers')
                        ->modalDescription('This will generate a PDF customer report for the selected records.')
                        ->visible(fn() => auth()->user()?->can('export customers') ?? false)
                        ->action(function (Collection $records) {
                            $records = $records->load(['createdBy', 'updatedBy']);

                            $generatedBy = auth()->user();
                            $generatedByRole = $generatedBy?->getRoleNames()?->first() ?? 'User';

                            $pdf = Pdf::loadView('pdfs.sales.customers-bulk-report', [
                                'customers' => $records,
                                'generatedBy' => $generatedBy,
                                'generatedByRole' => $generatedByRole,
                            ])->setPaper('a4', 'landscape');

                            return response()->streamDownload(
                                fn() => print ($pdf->output()),
                                'customers-report-' . now('Africa/Nairobi')->format('Ymd-His') . '.pdf'
                            );
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('delete customers') ?? false),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('restore customers') ?? false),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->can('force delete customers') ?? false),
                ]),
            ]);
    }

    public static function customerTypeOptions(): array
    {
        return [
            'individual' => 'Individual',
            'company' => 'Company',
            'farm' => 'Farm',
            'butcher' => 'Butcher',
            'broker' => 'Broker',
            'institution' => 'Institution',
            'other' => 'Other',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
