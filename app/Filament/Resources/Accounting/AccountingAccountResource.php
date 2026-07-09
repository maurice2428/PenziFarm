<?php

namespace App\Filament\Resources\Accounting;


use App\Filament\Resources\Accounting\AccountingAccountResource\Pages;
use App\Models\Accounting\AccountingAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountingAccountResource extends Resource
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
        return auth()->user()?->can('view accounting accounts') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting accounts') ?? false;
    }

    public static function canView($record): bool
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
        return auth()->user()?->can('delete accounting accounts') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete accounting accounts') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore accounting accounts') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore accounting accounts') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete accounting accounts') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete accounting accounts') ?? false;
    }
	protected static ?string $model = AccountingAccount::class;
	
	protected static ?string $navigationIcon = 'heroicon-o-book-open';
	protected static ?string $navigationGroup = 'Accounting';
	protected static ?string $navigationLabel = 'Chart of Accounts';
	protected static ?string $modelLabel = 'Account';
	protected static ?string $pluralModelLabel = 'Chart of Accounts';
	protected static ?int $navigationSort = 1;
	
	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\Section::make('Account Details')
			->columns(3)
			->schema([
				Forms\Components\TextInput::make('code')
				->required()
				->maxLength(30)
				->unique(ignoreRecord: true),
					 Forms\Components\TextInput::make('name')
					 ->required()
					 ->maxLength(255)
					 ->columnSpan(2),
					 Forms\Components\Select::make('type')
					 ->required()
					 ->options(AccountingAccount::typeOptions())
					 ->live()
					 ->afterStateUpdated(function ($state, Forms\Set $set): void {
						 $set('normal_balance', in_array($state, ['asset', 'expense', 'cost_of_sales'], true) ? 'debit' : 'credit');
					 }),
			Forms\Components\Select::make('normal_balance')
			->required()
			->options([
				'debit' => 'Debit',
			 'credit' => 'Credit',
			]),
			Forms\Components\Select::make('parent_id')
			->label('Parent Account')
			->relationship('parent', 'name', fn (Builder $query) => $query->orderBy('code'))
			->getOptionLabelFromRecordUsing(fn (AccountingAccount $record): string => $record->code . ' - ' . $record->name)
			->searchable()
			->preload(),
					 Forms\Components\TextInput::make('sort_order')
					 ->numeric()
					 ->default(0),
					 Forms\Components\Toggle::make('is_active')
					 ->default(true),
					 Forms\Components\Toggle::make('is_system')
					 ->default(false),
					 Forms\Components\Textarea::make('description')
					 ->columnSpanFull(),
			]),
		]);
	}
	
	public static function table(Table $table): Table
	{
		return $table
		->defaultSort('code')
		->columns([
			Tables\Columns\TextColumn::make('code')->searchable()->sortable()->weight('bold'),
				  Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
				  Tables\Columns\TextColumn::make('type')->badge()->formatStateUsing(fn (string $state): string => AccountingAccount::typeOptions()[$state] ?? $state),
				  Tables\Columns\TextColumn::make('normal_balance')->badge(),
				  Tables\Columns\TextColumn::make('parent.code')->label('Parent')->toggleable(),
				  Tables\Columns\IconColumn::make('is_active')->boolean(),
				  Tables\Columns\IconColumn::make('is_system')->boolean()->toggleable(isToggledHiddenByDefault: true),
		])
		->filters([
			Tables\Filters\SelectFilter::make('type')->options(AccountingAccount::typeOptions()),
				  Tables\Filters\TernaryFilter::make('is_active'),
		])
		->actions([
			Tables\Actions\EditAction::make(),
				  Tables\Actions\DeleteAction::make()->visible(fn (AccountingAccount $record): bool =>
                      ! $record->is_system
                      && (auth()->user()?->can('delete accounting accounts') ?? false)
                  ),
		])
		->bulkActions([
			Tables\Actions\BulkActionGroup::make([
				Tables\Actions\DeleteBulkAction::make(),
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