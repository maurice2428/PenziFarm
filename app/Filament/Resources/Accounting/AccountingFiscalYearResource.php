<?php

namespace App\Filament\Resources\Accounting;


use App\Filament\Resources\Accounting\AccountingFiscalYearResource\Pages;
use App\Models\Accounting\AccountingFiscalYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountingFiscalYearResource extends Resource
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
        return auth()->user()?->can('view accounting fiscal years') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting fiscal years') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view accounting fiscal years') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting fiscal years') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting fiscal years') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete accounting fiscal years') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete accounting fiscal years') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore accounting fiscal years') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore accounting fiscal years') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete accounting fiscal years') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete accounting fiscal years') ?? false;
    }
	protected static ?string $model = AccountingFiscalYear::class;
	protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
	protected static ?string $navigationGroup = 'Accounting';
	protected static ?string $navigationLabel = 'Fiscal Years';
	protected static ?int $navigationSort = 2;
	
	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\Section::make('Fiscal Year')
			->columns(3)
			->schema([
				Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true),
					 Forms\Components\DatePicker::make('start_date')->required(),
					 Forms\Components\DatePicker::make('end_date')->required(),
					 Forms\Components\Select::make('status')->required()->options([
						 'draft' => 'Draft',
						 'open' => 'Open',
						 'closed' => 'Closed',
						 'locked' => 'Locked',
					 ])->default('open'),
					 Forms\Components\Toggle::make('is_current')->default(false),
					 Forms\Components\Textarea::make('notes')->columnSpanFull(),
			]),
		]);
	}
	
	public static function table(Table $table): Table
	{
		return $table
		->defaultSort('start_date', 'desc')
		->columns([
			Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
				  Tables\Columns\TextColumn::make('start_date')->date()->sortable(),
				  Tables\Columns\TextColumn::make('end_date')->date()->sortable(),
				  Tables\Columns\TextColumn::make('status')->badge(),
				  Tables\Columns\IconColumn::make('is_current')->boolean(),
				  Tables\Columns\TextColumn::make('periods_count')->counts('periods')->label('Periods'),
		])
		->actions([
			Tables\Actions\EditAction::make(),
		]);
	}
	
	public static function getPages(): array
	{
		return [
			'index' => Pages\ListAccountingFiscalYears::route('/'),
			'create' => Pages\CreateAccountingFiscalYear::route('/create'),
			'edit' => Pages\EditAccountingFiscalYear::route('/{record}/edit'),
		];
	}
}