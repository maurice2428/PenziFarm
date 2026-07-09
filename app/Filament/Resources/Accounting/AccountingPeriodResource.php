<?php

namespace App\Filament\Resources\Accounting;


use App\Filament\Resources\Accounting\AccountingPeriodResource\Pages;
use App\Models\Accounting\AccountingPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountingPeriodResource extends Resource
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
        return auth()->user()?->can('view accounting periods') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting periods') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view accounting periods') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting periods') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting periods') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete accounting periods') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete accounting periods') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore accounting periods') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore accounting periods') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete accounting periods') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete accounting periods') ?? false;
    }
	protected static ?string $model = AccountingPeriod::class;
	protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
	protected static ?string $navigationGroup = 'Accounting';
	protected static ?string $navigationLabel = 'Accounting Periods';
	protected static ?int $navigationSort = 5;
	
	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\Section::make('Period')
			->columns(3)
			->schema([
				Forms\Components\Select::make('fiscal_year_id')->relationship('fiscalYear', 'name')->searchable()->preload()->required(),
					 Forms\Components\TextInput::make('name')->required(),
					 Forms\Components\TextInput::make('period_number')->numeric()->required(),
					 Forms\Components\DatePicker::make('start_date')->required(),
					 Forms\Components\DatePicker::make('end_date')->required(),
					 Forms\Components\Select::make('status')->required()->options([
						 'open' => 'Open',
						 'closed' => 'Closed',
						 'locked' => 'Locked',
					 ])->default('open'),
			]),
		]);
	}
	
	public static function table(Table $table): Table
	{
		return $table
		->defaultSort('start_date', 'desc')
		->columns([
			Tables\Columns\TextColumn::make('fiscalYear.name')->label('FY')->sortable(),
				  Tables\Columns\TextColumn::make('period_number')->sortable(),
				  Tables\Columns\TextColumn::make('name')->searchable(),
				  Tables\Columns\TextColumn::make('start_date')->date(),
				  Tables\Columns\TextColumn::make('end_date')->date(),
				  Tables\Columns\TextColumn::make('status')->badge(),
		])
		->actions([Tables\Actions\EditAction::make()]);
	}
	
	public static function getPages(): array
	{
		return [
			'index' => Pages\ListAccountingPeriods::route('/'),
			'create' => Pages\CreateAccountingPeriod::route('/create'),
			'edit' => Pages\EditAccountingPeriod::route('/{record}/edit'),
		];
	}
}