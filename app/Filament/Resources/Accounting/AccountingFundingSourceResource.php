<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingFundingSourceResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingFundingSource;
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

class AccountingFundingSourceResource extends Resource
{
    protected static ?string $model = AccountingFundingSource::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Project Funds';
    protected static ?string $navigationLabel = 'Funding Sources';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool { return auth()->user()?->can('view accounting funding sources') ?? false; }
    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }
    public static function getEloquentQuery(): Builder { return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Funding Source')->icon('heroicon-o-banknotes')
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('type')->native(false)->required()->options([
                        'director_capital' => 'Director Capital', 'director_loan' => 'Director Loan', 'grant' => 'Grant / Donor',
                        'bank_loan' => 'Bank Loan', 'operations' => 'Operations', 'investor' => 'Investor', 'other' => 'Other',
                    ])->default('director_capital'),
                    Forms\Components\Select::make('linked_account_id')->label('Linked Ledger Account')->options(fn (): array => AccountingAccount::query()->active()->leaf()->orderBy('code')->get()->mapWithKeys(fn ($a) => [$a->id => $a->code . ' · ' . $a->name])->all())->searchable()->preload()->native(false),
                    Forms\Components\TextInput::make('contact_person'),
                    Forms\Components\TextInput::make('phone')->tel(),
                    Forms\Components\TextInput::make('email')->email(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ]),
        ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting funding sources') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting funding sources') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete accounting funding sources') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('name')->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
            Tables\Columns\TextColumn::make('type')->badge(),
            Tables\Columns\TextColumn::make('linkedAccount.code')->label('Ledger')->placeholder('Not linked'),
            Tables\Columns\TextColumn::make('project_funds_count')->counts('projectFunds')->label('Projects')->badge(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('deleted_at')->label('Archived')->dateTime()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([Tables\Filters\SelectFilter::make('type')->options([
            'director_capital' => 'Director Capital', 'director_loan' => 'Director Loan', 'grant' => 'Grant / Donor', 'bank_loan' => 'Bank Loan', 'operations' => 'Operations', 'investor' => 'Investor', 'other' => 'Other',
        ]), Tables\Filters\TrashedFilter::make()])
        ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()->visible(fn (AccountingFundingSource $r): bool => ! $r->projectFunds()->exists() && ! $r->transactions()->exists()), Tables\Actions\RestoreAction::make()])
        ->bulkActions([
            Tables\Actions\BulkAction::make('activate')->label('Activate Selected')->color('success')->icon('heroicon-o-play')->action(function (Collection $records): void { foreach ($records as $r) { if ($r->trashed()) $r->restore(); $r->update(['is_active' => true]); } })->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('deactivate')->label('Deactivate Selected')->color('warning')->icon('heroicon-o-pause')->action(fn (Collection $records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('archiveEligible')->label('Archive Eligible')->color('danger')->icon('heroicon-o-archive-box')->requiresConfirmation()->action(function (Collection $records): void { $done=0;$skip=0; foreach ($records as $r) { if ($r->projectFunds()->exists() || $r->transactions()->exists()) {$skip++;continue;} $r->delete();$done++; } Notification::make()->title("{$done} archived; {$skip} skipped")->color($skip?'warning':'success')->send(); })->deselectRecordsAfterCompletion(),
            Tables\Actions\RestoreBulkAction::make(),
            Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn (Collection $records) => app(AccountingBulkExportService::class)->csv($records, ['Name'=>'name','Type'=>'type','Ledger'=>fn($r)=>$r->linkedAccount?->code,'Contact'=>'contact_person','Phone'=>'phone','Active'=>'is_active'], 'funding-sources-' . now()->format('Ymd_His') . '.csv')),
        ]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListAccountingFundingSources::route('/'),'create'=>Pages\CreateAccountingFundingSource::route('/create'),'edit'=>Pages\EditAccountingFundingSource::route('/{record}/edit')]; }
}
