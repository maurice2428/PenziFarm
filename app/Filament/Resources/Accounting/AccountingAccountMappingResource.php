<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingAccountMappingResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingAccountMapping;
use App\Services\Accounting\AccountingBulkExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AccountingAccountMappingResource extends Resource
{
    protected static ?string $model = AccountingAccountMapping::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Accounting Setup';
    protected static ?string $navigationLabel = 'Account Mappings';
    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool { return auth()->user()?->can('view accounting account mappings') ?? false; }
    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }

    private static function accountOptions(): array
    {
        return AccountingAccount::query()->active()->leaf()->orderBy('code')->get()
            ->mapWithKeys(fn ($a): array => [$a->id => $a->code . ' · ' . $a->name])->all();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting account mappings') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting account mappings') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Automatic Posting Mapping')->icon('heroicon-o-arrows-right-left')
                ->description('Map business events to active leaf ledger accounts. Required mappings block automated source posting when missing.')
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])->schema([
                    Forms\Components\TextInput::make('key')->required()->unique(ignoreRecord: true)->maxLength(100),
                    Forms\Components\TextInput::make('label')->required(),
                    Forms\Components\TextInput::make('module')->required()->default('global'),
                    Forms\Components\Select::make('account_id')->label('Ledger Account')->options(fn (): array => static::accountOptions())->searchable()->preload()->native(false),
                    Forms\Components\Toggle::make('is_required')->default(true),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('module')->columns([
            Tables\Columns\TextColumn::make('key')->searchable()->copyable()->weight('bold'),
            Tables\Columns\TextColumn::make('label')->searchable(),
            Tables\Columns\TextColumn::make('module')->badge(),
            Tables\Columns\TextColumn::make('account.code')->label('Account')->formatStateUsing(fn ($state, $record): string => $record->account ? $record->account->code . ' · ' . $record->account->name : 'NOT MAPPED')->color(fn ($record): string => $record->account ? 'success' : 'danger')->badge(),
            Tables\Columns\IconColumn::make('is_required')->boolean(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('module')->options(fn (): array => AccountingAccountMapping::query()->distinct()->orderBy('module')->pluck('module', 'module')->all()),
            Tables\Filters\TernaryFilter::make('is_active')->label('Active Status'),
        ])->actions([Tables\Actions\EditAction::make()])
        ->bulkActions([
            Tables\Actions\BulkAction::make('activate')->label('Activate Selected')->icon('heroicon-o-play')->color('success')->action(fn (Collection $records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('deactivate')->label('Deactivate Selected')->icon('heroicon-o-pause')->color('warning')->action(fn (Collection $records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('validate')->label('Validate Selected')->icon('heroicon-o-check-badge')->color('primary')->action(function (Collection $records): void {
                $invalid = $records->filter(fn ($r): bool => ! $r->account || ! $r->account->is_active || $r->account->children()->exists());
                Notification::make()->title($invalid->isEmpty() ? 'All selected mappings are valid' : $invalid->count() . ' mapping(s) need attention')->body($invalid->pluck('key')->join(', '))->color($invalid->isEmpty() ? 'success' : 'danger')->send();
            }),
            Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn (Collection $records) => app(AccountingBulkExportService::class)->csv($records, [
                'Key' => 'key', 'Label' => 'label', 'Module' => 'module', 'Account Code' => fn ($r) => $r->account?->code, 'Account Name' => fn ($r) => $r->account?->name, 'Required' => 'is_required', 'Active' => 'is_active',
            ], 'account-mappings-' . now()->format('Ymd_His') . '.csv')),
        ]);
    }

    public static function getPages(): array { return ['index' => Pages\ListAccountingAccountMappings::route('/'), 'create' => Pages\CreateAccountingAccountMapping::route('/create'), 'edit' => Pages\EditAccountingAccountMapping::route('/{record}/edit')]; }
}
