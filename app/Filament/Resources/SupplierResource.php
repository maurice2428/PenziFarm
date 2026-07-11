<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;
    protected static ?string $navigationGroup = 'Procurement';
    protected static ?string $navigationLabel = 'Suppliers';
    protected static ?string $navigationIcon =
        'heroicon-o-building-storefront';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view suppliers') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create suppliers') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit suppliers') ?? false;
    }

    public static function canDelete($record): bool
    {
        return (
            auth()->user()?->can('delete suppliers') ?? false
        ) && $record->canBeDeletedSafely();
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
        return $form->schema([
            Forms\Components\Section::make('Supplier Profile')
                ->description(
                    'Register vendors, agrovet suppliers, feed suppliers, '
                    . 'transporters, and service providers.'
                )
                ->icon('heroicon-o-building-storefront')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                ->schema([
                    Forms\Components\TextInput::make('company_name')
                        ->label('Supplier Name')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-building-office-2'),

                    Forms\Components\TextInput::make('contact_person')
                        ->label('Contact Person')
                        ->prefixIcon('heroicon-o-user'),

                    Forms\Components\TextInput::make('phone_primary')
                        ->label('Primary Phone')
                        ->tel()
                        ->prefixIcon('heroicon-o-phone'),

                    Forms\Components\TextInput::make('phone_secondary')
                        ->label('Secondary Phone')
                        ->tel()
                        ->prefixIcon('heroicon-o-device-phone-mobile'),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->prefixIcon('heroicon-o-envelope'),

                    Forms\Components\TextInput::make('kra_pin')
                        ->label('KRA PIN')
                        ->prefixIcon('heroicon-o-identification'),

                    Forms\Components\TextInput::make('physical_address')
                        ->label('Address')
                        ->columnSpanFull()
                        ->prefixIcon('heroicon-o-map-pin'),

                    Forms\Components\TextInput::make('bank_name')
                        ->label('Bank Name')
                        ->prefixIcon('heroicon-o-building-library'),

                    Forms\Components\TextInput::make('bank_account')
                        ->label('Bank Account')
                        ->prefixIcon('heroicon-o-credit-card'),

                    Forms\Components\Select::make('status')
                        ->default('active')
                        ->native(false)
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive / Archived',
                            'blocked' => 'Blocked',
                        ])
                        ->prefixIcon('heroicon-o-check-badge'),

                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('company_name')
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable()
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('phone_primary')
                    ->label('Phone')
                    ->searchable()
                    ->icon('heroicon-o-phone'),

                Tables\Columns\TextColumn::make('total_purchases')
                    ->label('Purchases')
                    ->money('KES'),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('KES')
                    ->color(
                        fn ($state): string =>
                            (float) $state > 0
                                ? 'warning'
                                : 'success'
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'blocked' => 'danger',
                        default => 'gray',
                    })
                    ->icon('heroicon-o-check-badge'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive / Archived',
                        'blocked' => 'Blocked',
                    ]),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth('5xl')
                    ->color('warning')
                    ->icon('heroicon-o-pencil-square'),

                Tables\Actions\Action::make('archiveSupplier')
                    ->label(
                        fn (Supplier $record): string =>
                            $record->status === 'active'
                                ? 'Archive'
                                : 'Activate'
                    )
                    ->icon(
                        fn (Supplier $record): string =>
                            $record->status === 'active'
                                ? 'heroicon-o-archive-box'
                                : 'heroicon-o-check-circle'
                    )
                    ->color(
                        fn (Supplier $record): string =>
                            $record->status === 'active'
                                ? 'gray'
                                : 'success'
                    )
                    ->action(
                        fn (Supplier $record) =>
                            $record->update([
                                'status' =>
                                    $record->status === 'active'
                                        ? 'inactive'
                                        : 'active',
                            ])
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('Delete Unused')
                    ->visible(
                        fn (Supplier $record): bool =>
                            static::canDelete($record)
                    )
                    ->requiresConfirmation(),

                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('archiveSelected')
                    ->label('Archive Selected')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('archive suppliers')
                            || auth()->user()?->can('edit suppliers')
                            || auth()->user()?->hasAnyRole([
                                'Administrator',
                                'Admin',
                            ])
                    )
                    ->action(function (Collection $records): void {
                        $records->each(
                            fn (Supplier $supplier) =>
                                $supplier->update([
                                    'status' => 'inactive',
                                ])
                        );

                        Notification::make()
                            ->success()
                            ->title('Selected suppliers archived')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('activateSelected')
                    ->label('Activate Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can('archive suppliers')
                            || auth()->user()?->can('edit suppliers')
                            || auth()->user()?->hasAnyRole([
                                'Administrator',
                                'Admin',
                            ])
                    )
                    ->action(function (Collection $records): void {
                        $activated = 0;
                        $skipped = 0;

                        foreach ($records as $supplier) {
                            if ($supplier->trashed()) {
                                $skipped++;
                                continue;
                            }

                            if ($supplier->status !== 'active') {
                                $supplier->update([
                                    'status' => 'active',
                                ]);
                                $activated++;
                            }
                        }

                        Notification::make()
                            ->title(
                                "{$activated} supplier(s) activated"
                            )
                            ->body(
                                "{$skipped} deleted supplier(s) must be "
                                . 'restored before activation.'
                            )
                            ->color(
                                $skipped > 0
                                    ? 'warning'
                                    : 'success'
                            )
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('deleteUnused')
                    ->label('Delete Selected Unused')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(
                        fn (): bool =>
                            auth()->user()?->can(
                                'delete suppliers'
                            )
                            || auth()->user()?->hasAnyRole([
                                'Administrator',
                                'Admin',
                            ])
                    )
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $deleted = 0;
                        $skipped = 0;

                        foreach ($records as $record) {
                            if (! $record->canBeDeletedSafely()) {
                                $skipped++;
                                continue;
                            }

                            $record->delete();
                            $deleted++;
                        }

                        Notification::make()
                            ->success()
                            ->title("{$deleted} supplier(s) deleted")
                            ->body(
                                "{$skipped} supplier(s) with procurement "
                                . 'history were retained and should be '
                                . 'archived instead.'
                            )
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
        ];
    }
}
