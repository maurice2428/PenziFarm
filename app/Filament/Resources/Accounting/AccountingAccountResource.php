<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingAccountResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Services\Accounting\AccountingBulkExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingAccountResource extends Resource
{
    protected static ?string $model = AccountingAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Accounting';
    protected static ?string $navigationLabel = 'Chart of Accounts';
    protected static ?string $modelLabel = 'Account';
    protected static ?string $pluralModelLabel = 'Chart of Accounts';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view accounting accounts') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting accounts') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting accounts') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting accounts') ?? false;
    }

    public static function canDelete($record): bool
    {
        return (auth()->user()?->can('delete accounting accounts') ?? false)
            && $record->canBeDeletedSafely();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ledger Account')
                ->description(
                    'Create a postable leaf account or a structural parent. '
                    . 'System and control accounts are protected from unsafe deletion.'
                )
                ->icon('heroicon-o-book-open')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->maxLength(30)
                        ->unique(ignoreRecord: true)
                        ->prefixIcon('heroicon-o-hashtag'),

                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(['default' => 1, 'xl' => 2])
                        ->prefixIcon('heroicon-o-tag'),

                    Forms\Components\Select::make('type')
                        ->required()
                        ->native(false)
                        ->options(AccountingAccount::typeOptions())
                        ->live()
                        ->afterStateUpdated(
                            fn ($state, Forms\Set $set) => $set(
                                'normal_balance',
                                in_array($state, ['asset', 'expense', 'cost_of_sales'], true)
                                    ? 'debit'
                                    : 'credit'
                            )
                        ),

                    Forms\Components\Select::make('normal_balance')
                        ->required()
                        ->native(false)
                        ->options([
                            'debit' => 'Debit',
                            'credit' => 'Credit',
                        ]),

                    Forms\Components\Select::make('parent_id')
                        ->label('Parent Account')
                        ->relationship(
                            'parent',
                            'name',
                            fn (Builder $query) => $query->orderBy('code')
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (AccountingAccount $record): string =>
                                $record->code . ' - ' . $record->name
                        )
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Forms\Components\TextInput::make('reporting_group')
                        ->label('Reporting Group')
                        ->placeholder('Current Assets, Payroll Taxes, Farm Income...'),

                    Forms\Components\TextInput::make('tax_code')
                        ->label('Default Tax Code')
                        ->placeholder('VAT_STANDARD, WHT_PROFESSIONAL...'),

                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true),

                    Forms\Components\Toggle::make('is_system')
                        ->label('Protected System Account')
                        ->default(false),

                    Forms\Components\Toggle::make('is_control_account')
                        ->label('Control Account')
                        ->default(false),

                    Forms\Components\Toggle::make('allow_manual_posting')
                        ->label('Allow Manual Journals')
                        ->default(true),

                    Forms\Components\Toggle::make('requires_cost_center')
                        ->default(false),

                    Forms\Components\Toggle::make('requires_project')
                        ->default(false),

                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(
                        fn (AccountingAccount $record): ?string =>
                            $record->reporting_group
                    ),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string =>
                            AccountingAccount::typeOptions()[$state] ?? $state
                    ),

                Tables\Columns\TextColumn::make('normal_balance')
                    ->badge(),

                Tables\Columns\TextColumn::make('parent.code')
                    ->label('Parent')
                    ->placeholder('Root')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('journal_lines_count')
                    ->counts('journalLines')
                    ->label('Postings')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_control_account')
                    ->label('Control')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Archived')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Active')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(AccountingAccount::typeOptions()),
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_control_account'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => static::canEdit(null)),

                Tables\Actions\Action::make('activate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (AccountingAccount $record): bool =>
                        ! $record->trashed() && ! $record->is_active
                    )
                    ->action(fn (AccountingAccount $record) =>
                        $record->update(['is_active' => true])
                    ),

                Tables\Actions\Action::make('deactivate')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (AccountingAccount $record): bool =>
                        ! $record->trashed()
                        && $record->is_active
                        && ! $record->is_system
                    )
                    ->action(fn (AccountingAccount $record) =>
                        $record->update(['is_active' => false])
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('Archive')
                    ->visible(fn (AccountingAccount $record): bool =>
                        static::canDelete($record)
                    ),

                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activateSelected')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! $record->trashed() && ! $record->is_active) {
                                    $record->update(['is_active' => true]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->success()
                                ->title("{$count} account(s) activated")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivateSelected')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! $record->trashed() && ! $record->is_system) {
                                    $record->update(['is_active' => false]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->success()
                                ->title("{$count} account(s) deactivated")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('archiveEligible')
                        ->label('Archive Eligible')
                        ->icon('heroicon-o-archive-box')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $archived = 0;
                            $skipped = 0;
                            foreach ($records as $record) {
                                if ($record->canBeDeletedSafely()) {
                                    $record->delete();
                                    $archived++;
                                } else {
                                    $skipped++;
                                }
                            }
                            Notification::make()
                                ->title("{$archived} account(s) archived")
                                ->body("{$skipped} protected or active account(s) skipped.")
                                ->color($skipped > 0 ? 'warning' : 'success')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\RestoreBulkAction::make(),

                    Tables\Actions\BulkAction::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(fn (Collection $records) =>
                            app(AccountingBulkExportService::class)->csv(
                                $records,
                                [
                                    'Code' => 'code',
                                    'Name' => 'name',
                                    'Type' => 'type',
                                    'Normal Balance' => 'normal_balance',
                                    'Reporting Group' => 'reporting_group',
                                    'Active' => fn ($record) => $record->is_active ? 'Yes' : 'No',
                                    'System' => fn ($record) => $record->is_system ? 'Yes' : 'No',
                                ],
                                'chart-of-accounts-' . now()->format('Ymd_His') . '.csv'
                            )
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountingAccounts::route('/'),
            'create' => Pages\CreateAccountingAccount::route('/create'),
            'edit' => Pages\EditAccountingAccount::route('/{record}/edit'),
        ];
    }
}
