<?php

namespace App\Filament\Resources\Accounting;


use App\Filament\Resources\Accounting\AccountingProjectFundResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingProjectFund;
use App\Services\Accounting\AccountingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountingProjectFundResource extends Resource
{

    /*
    |--------------------------------------------------------------------------
    | Permission access
    |--------------------------------------------------------------------------
    | Controlled by permissions assigned in the User Permissions tabs.
    | There is intentionally no hasRole() bypass here.
    */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view accounting project funds') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting project funds') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view accounting project funds') ?? false;
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

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete accounting project funds') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore accounting project funds') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore accounting project funds') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete accounting project funds') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete accounting project funds') ?? false;
    }
	protected static ?string $model = AccountingProjectFund::class;
	protected static ?string $navigationIcon = 'heroicon-o-folder-open';
	protected static ?string $navigationGroup = 'Project Funds';
	protected static ?string $navigationLabel = 'Project Funds';
	protected static ?int $navigationSort = 2;
	
	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\Section::make('Project Fund')
			->columns(3)
			->schema([
				Forms\Components\TextInput::make('fund_code')->required()->unique(ignoreRecord: true),
					 Forms\Components\TextInput::make('name')->required()->columnSpan(2),
					 Forms\Components\Select::make('funding_source_id')->relationship('fundingSource', 'name')->searchable()->preload(),
					 Forms\Components\Select::make('cost_center_id')->relationship('costCenter', 'name')->searchable()->preload(),
					 Forms\Components\Select::make('manager_id')->relationship('manager', 'name')->searchable()->preload(),
					 Forms\Components\Select::make('project_type')->required()->options([
						 'infrastructure' => 'Infrastructure',
						 'crop' => 'Crop',
						 'livestock' => 'Livestock',
						 'asset' => 'Asset',
						 'cctv' => 'CCTV',
						 'paddocking' => 'Paddocking',
						 'water' => 'Water',
						 'road' => 'Road',
						 'admin' => 'Admin',
						 'other' => 'Other',
					 ])->default('other'),
					 Forms\Components\Select::make('status')->required()->options([
						 'planned' => 'Planned',
						 'active' => 'Active',
						 'paused' => 'Paused',
						 'completed' => 'Completed',
						 'cancelled' => 'Cancelled',
					 ])->default('planned'),
					 Forms\Components\TextInput::make('budget_amount')->numeric()->prefix('KES')->default(0),
					 Forms\Components\DatePicker::make('start_date'),
					 Forms\Components\DatePicker::make('expected_end_date'),
					 Forms\Components\Textarea::make('description')->columnSpanFull(),
			]),
		]);
	}
	
	public static function table(Table $table): Table
	{
		return $table
		->defaultSort('created_at', 'desc')
		->columns([
			Tables\Columns\TextColumn::make('fund_code')->searchable()->sortable()->weight('bold'),
				  Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
				  Tables\Columns\TextColumn::make('project_type')->badge(),
				  Tables\Columns\TextColumn::make('status')->badge(),
				  Tables\Columns\TextColumn::make('budget_amount')->money('KES')->sortable(),
				  Tables\Columns\TextColumn::make('received_amount')->money('KES')->sortable(),
				  Tables\Columns\TextColumn::make('spent_amount')->money('KES')->sortable(),
				  Tables\Columns\TextColumn::make('balance_amount')->money('KES')->sortable(),
				  Tables\Columns\TextColumn::make('fundingSource.name')->label('Funding Source')->toggleable(),
		])
		->actions([
			Tables\Actions\Action::make('receiveFunds')
            ->visible(fn (): bool => auth()->user()?->can('create accounting project fund transactions') ?? false)
			->label('Receive Funds')
			->icon('heroicon-o-arrow-down-tray')
			->color('success')
			->form([
				Forms\Components\TextInput::make('amount')->numeric()->required()->prefix('KES'),
				   Forms\Components\Select::make('payment_method')->required()->options([
					   'cash' => 'Cash',
					   'bank' => 'Bank',
					   'mpesa' => 'M-Pesa',
					   'petty_cash' => 'Petty Cash',
				   ])->default('bank'),
				   Forms\Components\DatePicker::make('transaction_date')->required()->default(now()),
				   Forms\Components\TextInput::make('reference'),
				   Forms\Components\Textarea::make('narration'),
			])
			->action(function (AccountingProjectFund $record, array $data): void {
				app(AccountingService::class)->allocateProjectFund($record, $data);
				Notification::make()->title('Project funds received and journal posted')->success()->send();
			}),
			Tables\Actions\Action::make('recordExpense')
            ->visible(fn (): bool => auth()->user()?->can('create accounting project fund transactions') ?? false)
			->label('Record Expense')
			->icon('heroicon-o-arrow-up-tray')
			->color('warning')
			->form([
				Forms\Components\TextInput::make('amount')->numeric()->required()->prefix('KES'),
				   Forms\Components\Select::make('expense_account_id')
				   ->label('Expense/Asset Account')
				   ->required()
				   ->options(fn () => AccountingAccount::query()->active()->whereIn('type', ['expense', 'cost_of_sales', 'asset'])->orderBy('code')->get()->mapWithKeys(fn ($account) => [$account->id => $account->code . ' - ' . $account->name])->toArray())
				   ->searchable(),
				   Forms\Components\Select::make('payment_method')->required()->options([
					   'cash' => 'Cash',
					   'bank' => 'Bank',
					   'mpesa' => 'M-Pesa',
					   'petty_cash' => 'Petty Cash',
				   ])->default('bank'),
				   Forms\Components\DatePicker::make('transaction_date')->required()->default(now()),
				   Forms\Components\TextInput::make('reference'),
				   Forms\Components\TextInput::make('description'),
				   Forms\Components\Textarea::make('narration'),
			])
			->action(function (AccountingProjectFund $record, array $data): void {
				app(AccountingService::class)->recordProjectExpense($record, $data);
				Notification::make()->title('Project expense recorded and journal posted')->success()->send();
			}),
			Tables\Actions\EditAction::make(),
				  Tables\Actions\DeleteAction::make(),
		]);
	}
	
	public static function getPages(): array
	{
		return [
			'index' => Pages\ListAccountingProjectFunds::route('/'),
			'create' => Pages\CreateAccountingProjectFund::route('/create'),
			'edit' => Pages\EditAccountingProjectFund::route('/{record}/edit'),
		];
	}
}