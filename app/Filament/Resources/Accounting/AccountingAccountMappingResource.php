<?php

namespace App\Filament\Resources\Accounting;


use App\Filament\Resources\Accounting\AccountingAccountMappingResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingAccountMapping;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountingAccountMappingResource extends Resource
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
        return auth()->user()?->can('view accounting account mappings') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting account mappings') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view accounting account mappings') ?? false;
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
        return auth()->user()?->can('delete accounting account mappings') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete accounting account mappings') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore accounting account mappings') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore accounting account mappings') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete accounting account mappings') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete accounting account mappings') ?? false;
    }
	protected static ?string $model = AccountingAccountMapping::class;
	protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
	protected static ?string $navigationGroup = 'Accounting';
	protected static ?string $navigationLabel = 'Account Mappings';
	protected static ?int $navigationSort = 4;
	
	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\Section::make('Mapping')
			->columns(2)
			->schema([
				Forms\Components\TextInput::make('key')->required()->unique(ignoreRecord: true)->maxLength(100),
					 Forms\Components\TextInput::make('label')->required()->maxLength(255),
					 Forms\Components\TextInput::make('module')->required()->default('global'),
					 Forms\Components\Select::make('account_id')
					 ->label('Mapped Account')
					 ->options(fn () => AccountingAccount::query()->active()->orderBy('code')->get()->mapWithKeys(fn ($account) => [$account->id => $account->code . ' - ' . $account->name])->toArray())
					 ->searchable()
					 ->required(),
					 Forms\Components\Toggle::make('is_required')->default(true),
					 Forms\Components\Textarea::make('description')->columnSpanFull(),
			]),
		]);
	}
	
	public static function table(Table $table): Table
	{
		return $table
		->defaultSort('module')
		->columns([
			Tables\Columns\TextColumn::make('module')->badge()->searchable()->sortable(),
				  Tables\Columns\TextColumn::make('key')->searchable()->sortable()->weight('bold'),
				  Tables\Columns\TextColumn::make('label')->searchable(),
				  Tables\Columns\TextColumn::make('account.code')->label('Code'),
				  Tables\Columns\TextColumn::make('account.name')->label('Account')->searchable(),
				  Tables\Columns\IconColumn::make('is_required')->boolean(),
		])
		->filters([
			Tables\Filters\SelectFilter::make('module')->options(fn () => AccountingAccountMapping::query()->distinct()->pluck('module', 'module')->toArray()),
		])
		->actions([
			Tables\Actions\EditAction::make(),
		]);
	}
	
	public static function getPages(): array
	{
		return [
			'index' => Pages\ListAccountingAccountMappings::route('/'),
			'create' => Pages\CreateAccountingAccountMapping::route('/create'),
			'edit' => Pages\EditAccountingAccountMapping::route('/{record}/edit'),
		];
	}
}