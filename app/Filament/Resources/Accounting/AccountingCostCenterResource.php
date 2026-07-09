<?php

namespace App\Filament\Resources\Accounting;


use App\Filament\Resources\Accounting\AccountingCostCenterResource\Pages;
use App\Models\Accounting\AccountingCostCenter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountingCostCenterResource extends Resource
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
        return auth()->user()?->can('view accounting cost centers') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting cost centers') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view accounting cost centers') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting cost centers') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting cost centers') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete accounting cost centers') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete accounting cost centers') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore accounting cost centers') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore accounting cost centers') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete accounting cost centers') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete accounting cost centers') ?? false;
    }
	protected static ?string $model = AccountingCostCenter::class;
	protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
	protected static ?string $navigationGroup = 'Accounting';
	protected static ?string $navigationLabel = 'Cost Centers';
	protected static ?int $navigationSort = 6;
	
	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\Section::make('Cost Center')
			->columns(2)
			->schema([
				Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true),
					 Forms\Components\TextInput::make('name')->required(),
					 Forms\Components\Select::make('type')->required()->options([
						 'department' => 'Department',
						 'project' => 'Project',
						 'crop' => 'Crop',
						 'livestock' => 'Livestock',
						 'asset' => 'Asset',
						 'admin' => 'Admin',
						 'other' => 'Other',
					 ])->default('department'),
					 Forms\Components\Select::make('parent_id')->relationship('parent', 'name')->searchable()->preload(),
					 Forms\Components\Toggle::make('is_active')->default(true),
					 Forms\Components\Textarea::make('description')->columnSpanFull(),
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
				  Tables\Columns\TextColumn::make('type')->badge(),
				  Tables\Columns\TextColumn::make('parent.name')->label('Parent')->toggleable(),
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
			'index' => Pages\ListAccountingCostCenters::route('/'),
			'create' => Pages\CreateAccountingCostCenter::route('/create'),
			'edit' => Pages\EditAccountingCostCenter::route('/{record}/edit'),
		];
	}
}