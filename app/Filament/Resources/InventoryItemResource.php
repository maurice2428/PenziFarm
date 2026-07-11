<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryItemResource\Pages;
use App\Models\InventoryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Items';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view inventory items') ?? false;
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create inventory items') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit inventory items') ?? false;
    }

    public static function canDelete($record): bool
    {
        return (auth()->user()?->can('delete inventory items') ?? false)
            && $record->canBeDeletedSafely();
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore inventory items') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return (auth()->user()?->can('force delete inventory items') ?? false)
            && $record->canBeDeletedSafely();
    }

    public static function procurementCreateSchema(): array
    {
        return [
            Forms\Components\Section::make('Stock Item Identity')
                ->description(
                    'Create the central inventory item used by procurement, '
                    . 'stock receiving, health deductions, feeds, chemicals, '
                    . 'equipment and other operational purchases.'
                )
                ->icon('heroicon-o-cube')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Item Name')
                        ->required()
                        ->maxLength(255)
                        ->unique(
                            table: 'inventory_items',
                            column: 'name'
                        )
                        ->prefixIcon('heroicon-o-tag'),

                    Forms\Components\Select::make('category')
                        ->label('Stock Category')
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->options([
                            'vaccine' => 'Vaccine',
                            'dewormer' => 'Dewormer',
                            'dip' => 'Dipping Chemical',
                            'treatment' => 'Treatment Drug',
                            'feed' => 'Feed',
                            'chemical' => 'Chemical',
                            'equipment' => 'Equipment',
                        ])
                        ->prefixIcon('heroicon-o-squares-2x2'),

                    Forms\Components\Select::make('unit')
                        ->label('Stock Unit')
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->default('unit')
                        ->options([
                            'unit' => 'Unit',
                            'pcs' => 'Pieces',
                            'bottle' => 'Bottle',
                            'vial' => 'Vial',
                            'pack' => 'Pack',
                            'box' => 'Box',
                            'bag' => 'Bag',
                            'sachet' => 'Sachet',
                            'tablet' => 'Tablet',
                            'dose' => 'Dose',
                            'ml' => 'Millilitre',
                            'litre' => 'Litre',
                            'kg' => 'Kilogram',
                            'g' => 'Gram',
                            'metre' => 'Metre',
                        ])
                        ->prefixIcon('heroicon-o-scale'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active Stock Item')
                        ->default(true),
                ]),

            Forms\Components\Section::make('Stock Control & Valuation')
                ->description(
                    'Configure the opening quantity, replenishment levels, '
                    . 'default supplier cost and optional expiry date.'
                )
                ->icon('heroicon-o-chart-bar-square')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    Forms\Components\TextInput::make('opening_stock')
                        ->label('Opening Stock')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required()
                        ->prefixIcon('heroicon-o-archive-box'),

                    Forms\Components\TextInput::make('reorder_level')
                        ->label('Reorder Alert Level')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required()
                        ->prefixIcon('heroicon-o-bell-alert'),

                    Forms\Components\TextInput::make('order_level')
                        ->label('Target Order Level')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required()
                        ->prefixIcon('heroicon-o-shopping-cart'),

                    Forms\Components\TextInput::make('unit_cost')
                        ->label('Default Unit Cost')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required()
                        ->prefix('KES')
                        ->prefixIcon('heroicon-o-banknotes'),

                    Forms\Components\DatePicker::make('expiry_date')
                        ->label('Current / Default Expiry')
                        ->prefixIcon('heroicon-o-calendar-days'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Stock Notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(
            static::procurementCreateSchema()
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Stock Item')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-cube'),

                Tables\Columns\TextColumn::make('category_label')
                    ->label('Category')
                    ->badge()
                    ->color(fn ($record) => match ($record->category) {
                        'vaccine' => 'success',
                        'dewormer' => 'warning',
                        'dip' => 'info',
                        'treatment' => 'danger',
                        'feed' => 'gray',
                        'chemical' => 'warning',
                        'equipment' => 'primary',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->badge()
                    ->color(
                        fn ($record) =>
                            $record->is_low_stock
                                ? 'danger'
                                : 'success'
                    )
                    ->formatStateUsing(
                        fn ($state, $record) =>
                            number_format((float) $state, 2)
                            . ' '
                            . $record->unit
                    ),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder At')
                    ->formatStateUsing(
                        fn ($state, $record) =>
                            number_format((float) $state, 2)
                            . ' '
                            . $record->unit
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Stock Value')
                    ->money('KES'),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->date('d M Y')
                    ->placeholder('No expiry')
                    ->color(
                        fn ($record) =>
                            $record->expiry_date?->isPast()
                                ? 'danger'
                                : 'gray'
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'vaccine' => 'Vaccine',
                        'dewormer' => 'Dewormer',
                        'dip' => 'Dipping Chemical',
                        'treatment' => 'Treatment Drug',
                        'feed' => 'Feed',
                        'chemical' => 'Chemical',
                        'equipment' => 'Equipment',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth('6xl')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (InventoryItem $record): bool => static::canEdit($record)),

                Tables\Actions\Action::make('toggleActive')
                    ->label(
                        fn (InventoryItem $record): string =>
                            $record->is_active
                                ? 'Deactivate'
                                : 'Activate'
                    )
                    ->icon(
                        fn (InventoryItem $record): string =>
                            $record->is_active
                                ? 'heroicon-o-pause-circle'
                                : 'heroicon-o-play-circle'
                    )
                    ->color(
                        fn (InventoryItem $record): string =>
                            $record->is_active
                                ? 'warning'
                                : 'success'
                    )
                    ->visible(
                        fn (InventoryItem $record): bool =>
                            $record->is_active
                                ? (auth()->user()?->can('deactivate inventory items') ?? false)
                                : (auth()->user()?->can('activate inventory items') ?? false)
                    )
                    ->action(
                        fn (InventoryItem $record) =>
                            $record->update([
                                'is_active' =>
                                    ! $record->is_active,
                            ])
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('Delete Unused')
                    ->visible(
                        fn (InventoryItem $record): bool =>
                            static::canDelete($record)
                    )
                    ->requiresConfirmation(),

                Tables\Actions\RestoreAction::make()
                    ->visible(fn (InventoryItem $record): bool => static::canRestore($record)),

                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn (InventoryItem $record): bool => static::canForceDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make(
                    'deactivateSelected'
                )
                    ->label('Deactivate Selected')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('deactivate inventory items')
                            ?? false
                    )
                    ->action(
                        function (Collection $records): void {
                            $records->each(
                                fn (InventoryItem $item) =>
                                    $item->update([
                                        'is_active' => false,
                                    ])
                            );

                            Notification::make()
                                ->success()
                                ->title(
                                    'Selected stock items deactivated'
                                )
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make(
                    'activateSelected'
                )
                    ->label('Activate Selected')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('activate inventory items')
                            ?? false
                    )
                    ->action(
                        function (Collection $records): void {
                            $activated = 0;
                            $skipped = 0;

                            foreach ($records as $item) {
                                if ($item->trashed()) {
                                    $skipped++;
                                    continue;
                                }

                                if (! $item->is_active) {
                                    $item->update([
                                        'is_active' => true,
                                    ]);
                                    $activated++;
                                }
                            }

                            Notification::make()
                                ->title(
                                    "{$activated} stock item(s) activated"
                                )
                                ->body(
                                    "{$skipped} archived item(s) must be "
                                    . 'restored before activation.'
                                )
                                ->color(
                                    $skipped > 0
                                        ? 'warning'
                                        : 'success'
                                )
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make(
                    'deleteUnused'
                )
                    ->label('Delete Selected Unused')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('delete inventory items')
                            ?? false
                    )
                    ->requiresConfirmation()
                    ->action(
                        function (Collection $records): void {
                            $deleted = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (
                                    ! $record
                                        ->canBeDeletedSafely()
                                ) {
                                    $skipped++;
                                    continue;
                                }

                                $record->delete();
                                $deleted++;
                            }

                            Notification::make()
                                ->success()
                                ->title(
                                    "{$deleted} stock item(s) deleted"
                                )
                                ->body(
                                    "{$skipped} item(s) with ledger or "
                                    . 'procurement history were retained.'
                                )
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('exportSelected')
                    ->label('Export Selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (): bool => auth()->user()?->can('export inventory items') ?? false)
                    ->action(function (Collection $records) {
                        return response()->streamDownload(
                            function () use ($records): void {
                                $handle = fopen('php://output', 'wb');
                                fputcsv($handle, ['Name', 'Category', 'Unit', 'Current Stock', 'Unit Cost', 'Stock Value', 'Active']);
                                foreach ($records as $record) {
                                    fputcsv($handle, [
                                        $record->name,
                                        $record->category_label,
                                        $record->unit,
                                        $record->current_stock,
                                        $record->unit_cost,
                                        $record->stock_value,
                                        $record->is_active ? 'Yes' : 'No',
                                    ]);
                                }
                                fclose($handle);
                            },
                            'inventory-items-' . now('Africa/Nairobi')->format('Ymd_His') . '.csv',
                            ['Content-Type' => 'text/csv']
                        );
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\RestoreBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('restore inventory items') ?? false),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryItems::route('/'),
        ];
    }
}
