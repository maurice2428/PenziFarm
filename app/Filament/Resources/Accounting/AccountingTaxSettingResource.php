<?php

namespace App\Filament\Resources\Accounting;


use App\Filament\Resources\Accounting\AccountingTaxSettingResource\Pages;
use App\Models\Accounting\AccountingTaxSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountingTaxSettingResource extends Resource
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
        return auth()->user()?->can('view accounting tax settings') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting tax settings') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view accounting tax settings') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting tax settings') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting tax settings') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete accounting tax settings') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete accounting tax settings') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore accounting tax settings') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore accounting tax settings') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete accounting tax settings') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete accounting tax settings') ?? false;
    }
	protected static ?string $model = AccountingTaxSetting::class;
	protected static ?string $navigationIcon = 'heroicon-o-scale';
	protected static ?string $navigationGroup = 'Accounting';
	protected static ?string $navigationLabel = 'Tax Setting(s)';
	protected static ?int $navigationSort = 7;
	
	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\Section::make('Tax / Statutory Setting')
			->columns(3)
			->schema([
				Forms\Components\TextInput::make('name')->required(),
					 Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true),
					 Forms\Components\Select::make('type')->required()->options([
						 'vat' => 'VAT',
						 'paye' => 'PAYE',
						 'nssf' => 'NSSF',
						 'shif' => 'SHIF/SHA',
						 'housing_levy' => 'Affordable Housing Levy',
						 'withholding' => 'Withholding Tax',
						 'other' => 'Other',
					 ])->default('other'),
					 Forms\Components\TextInput::make('rate')->numeric()->suffix('%'),
					 Forms\Components\TextInput::make('fixed_amount')->numeric()->prefix('KES'),
					 Forms\Components\Toggle::make('is_active')->default(true),
					 Forms\Components\DatePicker::make('effective_from'),
					 Forms\Components\DatePicker::make('effective_to'),
					 Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
			]),
		]);
	}
	
	public static function table(Table $table): Table
	{
		return $table
		->defaultSort('type')
		->columns([
			Tables\Columns\TextColumn::make('type')->badge()->sortable(),
				  Tables\Columns\TextColumn::make('code')->searchable()->sortable()->weight('bold'),
				  Tables\Columns\TextColumn::make('name')->searchable(),
				  Tables\Columns\TextColumn::make('rate')->suffix('%'),
				  Tables\Columns\TextColumn::make('fixed_amount')->money('KES'),
				  Tables\Columns\TextColumn::make('effective_from')->date(),
				  Tables\Columns\IconColumn::make('is_active')->boolean(),
		])
		->actions([
			Tables\Actions\EditAction::make(),
				  Tables\Actions\DeleteAction::make(),
		]);
	}
	
	public static function getPages(): array
	{
		return [
			'index' => Pages\ListAccountingTaxSettings::route('/'),
			'create' => Pages\CreateAccountingTaxSetting::route('/create'),
			'edit' => Pages\EditAccountingTaxSetting::route('/{record}/edit'),
		];
	}
}