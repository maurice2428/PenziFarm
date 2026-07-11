<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingProjectFundResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingProjectFund;
use App\Services\Accounting\AccountingBulkExportService;
use App\Services\Accounting\AccountingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingProjectFundResource extends Resource
{
    protected static ?string $model = AccountingProjectFund::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationGroup = 'Project Funds';
    protected static ?string $navigationLabel = 'Project Funds';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool { return auth()->user()?->can('view accounting project funds') ?? false; }
    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }
    public static function getEloquentQuery(): Builder { return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Project Fund')->icon('heroicon-o-folder-open')
                ->columns(['default'=>1,'md'=>2,'xl'=>4])->schema([
                    Forms\Components\TextInput::make('fund_code')->required()->unique(ignoreRecord:true)->maxLength(50),
                    Forms\Components\TextInput::make('name')->required()->columnSpan(['default'=>1,'xl'=>2]),
                    Forms\Components\Select::make('project_type')->native(false)->required()->options([
                        'infrastructure'=>'Infrastructure','crop'=>'Crop','livestock'=>'Livestock','asset'=>'Asset','cctv'=>'CCTV','paddocking'=>'Paddocking','water'=>'Water','road'=>'Road','admin'=>'Administration','other'=>'Other',
                    ])->default('other'),
                    Forms\Components\Select::make('funding_source_id')->relationship('fundingSource','name')->searchable()->preload()->native(false),
                    Forms\Components\Select::make('cost_center_id')->relationship('costCenter','name')->searchable()->preload()->native(false),
                    Forms\Components\Select::make('manager_id')->relationship('manager','name')->searchable()->preload()->native(false),
                    Forms\Components\Select::make('status')->native(false)->required()->options(['planned'=>'Planned','active'=>'Active','paused'=>'Paused','completed'=>'Completed','cancelled'=>'Cancelled'])->default('planned'),
                    Forms\Components\TextInput::make('budget_amount')->numeric()->minValue(0)->prefix('KES')->default(0),
                    Forms\Components\DatePicker::make('start_date')->native(false),
                    Forms\Components\DatePicker::make('expected_end_date')->native(false)->afterOrEqual('start_date'),
                    Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting project funds') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting project funds') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete accounting project funds') ?? false;
    }

    private static function receiveForm(): array
    {
        return [
            Forms\Components\TextInput::make('amount')->numeric()->minValue(0.01)->required()->prefix('KES'),
            Forms\Components\Select::make('payment_method')->native(false)->required()->options(['cash'=>'Cash','bank'=>'Bank','mpesa'=>'M-Pesa','petty_cash'=>'Petty Cash','other'=>'Other'])->default('bank'),
            Forms\Components\DatePicker::make('transaction_date')->native(false)->required()->default(now()),
            Forms\Components\TextInput::make('reference'),
            Forms\Components\Textarea::make('narration')->rows(3),
        ];
    }

    private static function expenseForm(): array
    {
        return [
            Forms\Components\TextInput::make('amount')->numeric()->minValue(0.01)->required()->prefix('KES'),
            Forms\Components\Select::make('expense_account_id')->label('Expense / Asset Account')->required()->native(false)->options(fn (): array => AccountingAccount::query()->active()->leaf()->whereIn('type',['expense','cost_of_sales','asset'])->orderBy('code')->get()->mapWithKeys(fn($a)=>[$a->id=>$a->code.' · '.$a->name])->all())->searchable()->preload(),
            Forms\Components\Select::make('payment_method')->native(false)->required()->options(['cash'=>'Cash','bank'=>'Bank','mpesa'=>'M-Pesa','petty_cash'=>'Petty Cash','other'=>'Other'])->default('bank'),
            Forms\Components\DatePicker::make('transaction_date')->native(false)->required()->default(now()),
            Forms\Components\TextInput::make('reference'),
            Forms\Components\TextInput::make('description'),
            Forms\Components\Textarea::make('narration')->rows(3),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at','desc')->columns([
            Tables\Columns\TextColumn::make('fund_code')->searchable()->sortable()->weight('bold'),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('project_type')->badge(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('budget_amount')->money('KES')->sortable(),
            Tables\Columns\TextColumn::make('received_amount')->money('KES')->sortable(),
            Tables\Columns\TextColumn::make('spent_amount')->money('KES')->sortable(),
            Tables\Columns\TextColumn::make('balance_amount')->money('KES')->sortable(),
            Tables\Columns\TextColumn::make('utilization_percent')->label('Utilisation')->suffix('%')->badge()->color(fn($state):string => (float)$state >= 100 ? 'danger' : ((float)$state >= 80 ? 'warning' : 'success')),
            Tables\Columns\TextColumn::make('fundingSource.name')->label('Funding Source')->toggleable(),
            Tables\Columns\TextColumn::make('deleted_at')->dateTime()->label('Archived')->toggleable(isToggledHiddenByDefault:true),
        ])->filters([
            Tables\Filters\SelectFilter::make('project_type')->options(['infrastructure'=>'Infrastructure','crop'=>'Crop','livestock'=>'Livestock','asset'=>'Asset','cctv'=>'CCTV','paddocking'=>'Paddocking','water'=>'Water','road'=>'Road','admin'=>'Administration','other'=>'Other']),
            Tables\Filters\SelectFilter::make('status')->options(['planned'=>'Planned','active'=>'Active','paused'=>'Paused','completed'=>'Completed','cancelled'=>'Cancelled']),
            Tables\Filters\TrashedFilter::make(),
        ])->actions([
            Tables\Actions\Action::make('receiveFunds')->label('Receive Funds')->icon('heroicon-o-arrow-down-tray')->color('success')->slideOver()->modalWidth('3xl')->form(static::receiveForm())
                ->visible(fn():bool => auth()->user()?->can('create accounting project fund transactions') ?? false)
                ->action(function(AccountingProjectFund $record,array $data):void { app(AccountingService::class)->allocateProjectFund($record,$data); Notification::make()->success()->title('Funds received and journal posted')->send(); }),
            Tables\Actions\Action::make('recordExpense')->label('Record Expense')->icon('heroicon-o-arrow-up-tray')->color('warning')->slideOver()->modalWidth('3xl')->form(static::expenseForm())
                ->visible(fn():bool => auth()->user()?->can('create accounting project fund transactions') ?? false)
                ->action(function(AccountingProjectFund $record,array $data):void { app(AccountingService::class)->recordProjectExpense($record,$data); Notification::make()->success()->title('Expense posted')->send(); }),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()->visible(fn(AccountingProjectFund $r):bool => ! $r->transactions()->exists() && ! $r->journalLines()->exists()),
            Tables\Actions\RestoreAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('activate')->label('Set Active')->color('success')->icon('heroicon-o-play')->action(function(Collection $records):void { foreach($records as $r){if($r->trashed())$r->restore();$r->update(['status'=>'active']);} })->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('pause')->label('Pause Selected')->color('warning')->icon('heroicon-o-pause')->action(fn(Collection $records)=>$records->each->update(['status'=>'paused']))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('complete')->label('Complete Selected')->color('primary')->icon('heroicon-o-check-badge')->requiresConfirmation()->action(fn(Collection $records)=>$records->each->update(['status'=>'completed']))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('archiveEligible')->label('Archive Eligible')->color('danger')->icon('heroicon-o-archive-box')->requiresConfirmation()->action(function(Collection $records):void{$done=0;$skip=0;foreach($records as $r){if($r->transactions()->exists()||$r->journalLines()->exists()){$skip++;continue;}$r->delete();$done++;}Notification::make()->title("{$done} archived; {$skip} skipped")->color($skip?'warning':'success')->send();})->deselectRecordsAfterCompletion(),
            Tables\Actions\RestoreBulkAction::make(),
            Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn(Collection $records)=>app(AccountingBulkExportService::class)->csv($records,['Code'=>'fund_code','Name'=>'name','Type'=>'project_type','Status'=>'status','Budget'=>'budget_amount','Received'=>'received_amount','Spent'=>'spent_amount','Balance'=>'balance_amount'],'project-funds-'.now()->format('Ymd_His').'.csv')),
        ]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListAccountingProjectFunds::route('/'),'create'=>Pages\CreateAccountingProjectFund::route('/create'),'edit'=>Pages\EditAccountingProjectFund::route('/{record}/edit')]; }
}
