<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingReconciliationResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingReconciliation;
use App\Services\Accounting\AccountingBulkExportService;
use App\Services\Accounting\AccountingReconciliationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AccountingReconciliationResource extends Resource
{
    protected static ?string $model = AccountingReconciliation::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Accounting Controls';
    protected static ?string $navigationLabel = 'Bank & Cash Reconciliations';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool { return auth()->user()?->can('view accounting reconciliations') ?? false; }
    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Reconciliation Statement')->icon('heroicon-o-arrows-right-left')
                ->columns(['default'=>1,'md'=>2,'xl'=>4])->schema([
                    Forms\Components\TextInput::make('reconciliation_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('account_id')->label('Bank / Cash Account')->options(fn():array=>AccountingAccount::query()->active()->leaf()->where('type','asset')->orderBy('code')->get()->mapWithKeys(fn($a)=>[$a->id=>$a->code.' · '.$a->name])->all())->searchable()->preload()->native(false)->required(),
                    Forms\Components\DatePicker::make('statement_date')->native(false)->required()->default(now()),
                    Forms\Components\TextInput::make('statement_balance')->numeric()->required()->prefix('KES'),
                    Forms\Components\TextInput::make('opening_balance')->numeric()->prefix('KES')->default(0),
                    Forms\Components\TextInput::make('system_balance')->numeric()->prefix('KES')->readOnly()->dehydrated(),
                    Forms\Components\TextInput::make('difference')->numeric()->prefix('KES')->readOnly()->dehydrated(),
                    Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting reconciliations') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting reconciliations') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('statement_date','desc')->columns([
            Tables\Columns\TextColumn::make('reconciliation_number')->searchable()->weight('bold')->copyable(),
            Tables\Columns\TextColumn::make('account.code')->label('Account')->formatStateUsing(fn($state,$r):string=>$r->account?->code.' · '.$r->account?->name)->searchable(),
            Tables\Columns\TextColumn::make('statement_date')->date('d M Y')->sortable(),
            Tables\Columns\TextColumn::make('statement_balance')->money('KES')->alignEnd(),
            Tables\Columns\TextColumn::make('system_balance')->money('KES')->alignEnd(),
            Tables\Columns\TextColumn::make('difference')->money('KES')->alignEnd()->color(fn($state):string=>abs((float)$state)<0.01?'success':'danger'),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn(string $state):string=>match($state){'reconciled'=>'success','void'=>'danger',default=>'gray'}),
            Tables\Columns\TextColumn::make('approvedBy.name')->label('Approved By')->placeholder('-')->toggleable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(['draft'=>'Draft','reconciled'=>'Reconciled','void'=>'Void']),
            Tables\Filters\SelectFilter::make('account_id')->relationship('account','name')->searchable()->preload(),
        ])->actions([
            Tables\Actions\EditAction::make()->visible(fn(AccountingReconciliation $r):bool=>$r->status==='draft'),
            Tables\Actions\Action::make('refresh')->label('Refresh Balance')->icon('heroicon-o-arrow-path')->color('gray')->action(fn(AccountingReconciliation $r)=>app(AccountingReconciliationService::class)->refreshSystemBalance($r)),
            Tables\Actions\Action::make('approve')->icon('heroicon-o-check-badge')->color('primary')->requiresConfirmation()->visible(fn(AccountingReconciliation $r):bool=>$r->status==='draft'&&!$r->approved_at)->action(fn(AccountingReconciliation $r)=>app(AccountingReconciliationService::class)->approve($r)),
            Tables\Actions\Action::make('complete')->label('Complete')->icon('heroicon-o-lock-closed')->color('success')->requiresConfirmation()->visible(fn(AccountingReconciliation $r):bool=>$r->status==='draft')->action(fn(AccountingReconciliation $r)=>app(AccountingReconciliationService::class)->complete($r)),
            Tables\Actions\Action::make('reopen')->icon('heroicon-o-lock-open')->color('warning')->requiresConfirmation()->visible(fn(AccountingReconciliation $r):bool=>$r->status==='reconciled')->action(fn(AccountingReconciliation $r)=>app(AccountingReconciliationService::class)->reopen($r)),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('refreshSelected')->label('Refresh Selected')->icon('heroicon-o-arrow-path')->color('gray')->action(fn(Collection $records)=>$records->each(fn($r)=>app(AccountingReconciliationService::class)->refreshSystemBalance($r)))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('approveSelected')->label('Approve Selected')->icon('heroicon-o-check-badge')->color('primary')->action(fn(Collection $records)=>$records->where('status','draft')->each(fn($r)=>app(AccountingReconciliationService::class)->approve($r)))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('completeSelected')->label('Complete Balanced')->icon('heroicon-o-lock-closed')->color('success')->requiresConfirmation()->action(function(Collection $records):void{$done=0;$skip=0;foreach($records->where('status','draft') as $r){try{app(AccountingReconciliationService::class)->complete($r);$done++;}catch(\Throwable){$skip++;}}Notification::make()->title("{$done} completed; {$skip} skipped")->color($skip?'warning':'success')->send();})->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('reopenSelected')->label('Reopen Selected')->icon('heroicon-o-lock-open')->color('warning')->requiresConfirmation()->action(fn(Collection $records)=>$records->where('status','reconciled')->each(fn($r)=>app(AccountingReconciliationService::class)->reopen($r)))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn(Collection $records)=>app(AccountingBulkExportService::class)->csv($records,['Number'=>'reconciliation_number','Account'=>fn($r)=>$r->account?->code.' '.$r->account?->name,'Statement Date'=>fn($r)=>$r->statement_date?->format('Y-m-d'),'Statement Balance'=>'statement_balance','System Balance'=>'system_balance','Difference'=>'difference','Status'=>'status'],'reconciliations-'.now()->format('Ymd_His').'.csv')),
        ]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListAccountingReconciliations::route('/'),'create'=>Pages\CreateAccountingReconciliation::route('/create'),'edit'=>Pages\EditAccountingReconciliation::route('/{record}/edit')]; }
}
