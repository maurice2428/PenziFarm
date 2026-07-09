<?php

namespace App\Filament\Resources\Accounting;


use App\Filament\Resources\Accounting\AccountingFundingSourceResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingFundingSource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountingFundingSourceResource extends Resource
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
        return auth()->user()?->can('view accounting funding sources') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting funding sources') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view accounting funding sources') ?? false;
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

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete accounting funding sources') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore accounting funding sources') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore accounting funding sources') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete accounting funding sources') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete accounting funding sources') ?? false;
    }
	protected static ?string $model = AccountingFundingSource::class;
	protected static ?string $navigationIcon = 'heroicon-o-banknotes';
	protected static ?string $navigationGroup = 'Project Funds';
	protected static ?string $navigationLabel = 'Funding Sources';
	protected static ?int $navigationSort = 1;
	
	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\Section::make('Funding Source')
			->columns(2)
			->schema([
				Forms\Components\TextInput::make('name')->required()->maxLength(255),
					 Forms\Components\Select::make('type')->required()->options([
						 'director_capital' => 'Director Capital',
						 'director_loan' => 'Director Loan',
						 'grant' => 'Grant/Donor Funding',
						 'bank_loan' => 'Bank Loan',
						 'operations' => 'Farm Operations Income',
						 'investor' => 'Investor Funding',
						 'other' => 'Other',
					 ])->default('director_capital'),
					 Forms\Components\Select::make('linked_account_id')
					 ->label('Linked Ledger Account')
					 ->options(fn () => AccountingAccount::query()->active()->orderBy('code')->get()->mapWithKeys(fn ($account) => [$account->id => $account->code . ' - ' . $account->name])->toArray())
					 ->searchable(),
					 Forms\Components\Toggle::make('is_active')->default(true),
					 Forms\Components\TextInput::make('contact_person'),
					 Forms\Components\TextInput::make('phone')->tel(),
					 Forms\Components\TextInput::make('email')->email(),
					 Forms\Components\Textarea::make('notes')->columnSpanFull(),
			]),
		]);
	}
	
	public static function table(Table $table): Table
	{
		return $table
		->defaultSort('name')
		->columns([
			Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
				  Tables\Columns\TextColumn::make('type')->badge()->sortable(),
				  Tables\Columns\TextColumn::make('linkedAccount.code')->label('Account'),
				  Tables\Columns\TextColumn::make('project_funds_count')->counts('projectFunds')->label('Projects'),
				  Tables\Columns\IconColumn::make('is_active')->boolean(),
		])
		->filters([
			Tables\Filters\SelectFilter::make('type')->options([
				'director_capital' => 'Director Capital',
				'director_loan' => 'Director Loan',
				'grant' => 'Grant/Donor Funding',
				'bank_loan' => 'Bank Loan',
				'operations' => 'Operations',
				'investor' => 'Investor',
				'other' => 'Other',
			]),
		])
		->actions([
			Tables\Actions\EditAction::make(),
				  Tables\Actions\DeleteAction::make(),
		]);
	}
	
	public static function getPages(): array
	{
		return [
			'index' => Pages\ListAccountingFundingSources::route('/'),
			'create' => Pages\CreateAccountingFundingSource::route('/create'),
			'edit' => Pages\EditAccountingFundingSource::route('/{record}/edit'),
		];
	}
}