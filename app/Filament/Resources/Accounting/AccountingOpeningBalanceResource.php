<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingOpeningBalanceResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingFiscalYear;
use App\Models\Accounting\AccountingOpeningBalance;
use App\Services\Accounting\AccountingBulkExportService;
use App\Services\Accounting\AccountingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AccountingOpeningBalanceResource extends Resource
{
    protected static ?string $model = AccountingOpeningBalance::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';
    protected static ?string $navigationGroup = 'Accounting Setup';
    protected static ?string $navigationLabel = 'Opening Balances';
    protected static ?int $navigationSort = 6;

    public static function shouldRegisterNavigation(): bool { return auth()->user()?->can('view accounting opening balances') ?? false; }
    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Opening Balance')->icon('heroicon-o-arrow-right-start-on-rectangle')
                ->description('Enter one debit or one credit. Post selected balances only after the complete opening trial balance is ready.')
                ->columns(['default'=>1,'md'=>2,'xl'=>4])->schema([
                    Forms\Components\Select::make('fiscal_year_id')->relationship('fiscalYear','name')->searchable()->preload()->native(false)->required(),
                    Forms\Components\Select::make('account_id')->label('Ledger Account')->options(fn():array=>AccountingAccount::query()->active()->leaf()->orderBy('code')->get()->mapWithKeys(fn($a)=>[$a->id=>$a->code.' · '.$a->name])->all())->searchable()->preload()->native(false)->required(),
                    Forms\Components\TextInput::make('debit')->numeric()->minValue(0)->default(0)->prefix('KES')->required(),
                    Forms\Components\TextInput::make('credit')->numeric()->minValue(0)->default(0)->prefix('KES')->required(),
                    Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting opening balances') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting opening balances') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete accounting opening balances') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id','desc')->columns([
            Tables\Columns\TextColumn::make('fiscalYear.name')->label('Fiscal Year')->sortable(),
            Tables\Columns\TextColumn::make('account.code')->label('Code')->searchable(),
            Tables\Columns\TextColumn::make('account.name')->label('Account')->searchable()->weight('bold'),
            Tables\Columns\TextColumn::make('debit')->money('KES')->alignEnd(),
            Tables\Columns\TextColumn::make('credit')->money('KES')->alignEnd(),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn(string $state):string=>$state==='posted'?'success':'gray'),
            Tables\Columns\TextColumn::make('journalEntry.journal_number')->label('Journal')->placeholder('-')->copyable(),
            Tables\Columns\TextColumn::make('posted_at')->dateTime('d M Y H:i')->placeholder('-'),
        ])->filters([
            Tables\Filters\SelectFilter::make('fiscal_year_id')->relationship('fiscalYear','name')->searchable()->preload(),
            Tables\Filters\SelectFilter::make('status')->options(['draft'=>'Draft','posted'=>'Posted']),
        ])->actions([
            Tables\Actions\EditAction::make()->visible(fn(AccountingOpeningBalance $r):bool=>$r->status==='draft'),
            Tables\Actions\DeleteAction::make()->visible(fn(AccountingOpeningBalance $r):bool=>$r->status==='draft'),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('postSelected')->label('Post Selected Drafts')->icon('heroicon-o-paper-airplane')->color('success')->requiresConfirmation()
                ->action(function(Collection $records):void { $posted=0; foreach($records->where('status','draft')->groupBy('fiscal_year_id') as $fyId=>$items){$fy=AccountingFiscalYear::query()->findOrFail($fyId); app(AccountingService::class)->postOpeningBalances($fy,$items->pluck('id')->all());$posted+=$items->count();} Notification::make()->success()->title("{$posted} opening balance(s) posted")->send(); })->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('deleteDrafts')->label('Delete Selected Drafts')->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()->action(function(Collection $records):void{$records->where('status','draft')->each->delete();})->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn(Collection $records)=>app(AccountingBulkExportService::class)->csv($records,['Fiscal Year'=>fn($r)=>$r->fiscalYear?->name,'Code'=>fn($r)=>$r->account?->code,'Account'=>fn($r)=>$r->account?->name,'Debit'=>'debit','Credit'=>'credit','Status'=>'status'],'opening-balances-'.now()->format('Ymd_His').'.csv')),
        ]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListAccountingOpeningBalances::route('/'),'create'=>Pages\CreateAccountingOpeningBalance::route('/create'),'edit'=>Pages\EditAccountingOpeningBalance::route('/{record}/edit')]; }
}
