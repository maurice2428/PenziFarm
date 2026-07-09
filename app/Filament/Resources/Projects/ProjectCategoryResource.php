<?php

namespace App\Filament\Resources\Projects;


use App\Filament\Resources\Projects\ProjectCategoryResource\Pages;
use App\Models\Projects\ProjectCategory;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class ProjectCategoryResource extends Resource
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
        return auth()->user()?->can('view project categories') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view project categories') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view project categories') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create project categories') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit project categories') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete project categories') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete project categories') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore project categories') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore project categories') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete project categories') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete project categories') ?? false;
    }
	protected static ?string $model = ProjectCategory::class;
	
	protected static ?string $navigationGroup = 'Projects & Works';
	
	protected static ?string $navigationLabel = 'Categories';
	
	protected static ?string $modelLabel = 'Category';
	
	protected static ?string $pluralModelLabel = 'Categories';
	
	protected static ?string $navigationIcon = 'heroicon-o-tag';
	
	protected static ?int $navigationSort = 1;
	
	protected static ?string $slug = 'projects/categories';
	
	
	public static function form(Form $form): Form
	{
		return $form
		->schema([
			Forms\Components\Section::make('Category Details')
			->description('Classify projects such as buildings, fencing, roads, dams, repairs, and infrastructure works.')
			->icon('heroicon-o-tag')
			->columns(12)
			->schema([
				Forms\Components\TextInput::make('name')
				->label('Category Name')
				->required()
				->maxLength(255)
				->columnSpan(4),
					 Forms\Components\TextInput::make('code')
					 ->label('Code')
					 ->maxLength(50)
					 ->unique(ignoreRecord: true)
					 ->columnSpan(2),
					 Forms\Components\Select::make('type')
					 ->label('Type')
					 ->options([
						 'building' => 'Building / Structures',
				'construction' => 'Construction',
				'dam' => 'Dam / Water Works',
				'road' => 'Road Works',
				'fencing' => 'Fencing / Paddocking',
				'renovation' => 'Renovation',
				'repair' => 'Repair / Maintenance',
				'electrical' => 'Electrical Works',
				'plumbing' => 'Plumbing / Water Lines',
				'security' => 'Security / CCTV',
				'land_preparation' => 'Land Preparation',
				'other' => 'Other',
					 ])
					 ->default('other')
					 ->searchable()
					 ->required()
					 ->columnSpan(3),
					 Forms\Components\TextInput::make('icon')
					 ->label('Icon')
					 ->placeholder('heroicon-o-building-office-2')
					 ->maxLength(100)
					 ->columnSpan(3),
					 Forms\Components\ColorPicker::make('color')
					 ->label('Category Color')
					 ->default('#166534')
					 ->columnSpan(2),
					 Forms\Components\Toggle::make('is_active')
					 ->label('Active')
					 ->default(true)
					 ->columnSpan(2),
					 Forms\Components\Textarea::make('description')
					 ->label('Description')
					 ->rows(4)
					 ->columnSpanFull(),
					 Forms\Components\Hidden::make('created_by')
					 ->default(fn() => auth()->id()),
			]),
		]);
	}
	
	public static function table(Table $table): Table
	{
		return $table
		->defaultSort('name')
		->columns([
			Tables\Columns\TextColumn::make('name')
			->label('Category')
			->searchable()
			->sortable()
			->weight('bold')
			->icon('heroicon-o-tag'),
				  Tables\Columns\TextColumn::make('code')
				  ->label('Code')
				  ->badge()
				  ->color('gray')
				  ->searchable()
				  ->toggleable(),
				  Tables\Columns\TextColumn::make('type')
				  ->label('Type')
				  ->badge()
				  ->formatStateUsing(fn(?string $state): string => str($state ?: 'other')->replace('_', ' ')->headline())
				  ->color(fn(?string $state): string => match ($state) {
					  'building', 'construction' => 'info',
					  'dam', 'plumbing' => 'sky',
					  'road', 'fencing' => 'warning',
					  'repair', 'renovation' => 'success',
					  'security', 'electrical' => 'danger',
					  default => 'gray',
				  })
				  ->sortable(),
				  Tables\Columns\IconColumn::make('is_active')
				  ->label('Active')
				  ->boolean(),
				  Tables\Columns\TextColumn::make('projects_count')
				  ->label('Projects')
				  ->counts('projects')
				  ->badge()
				  ->color('success'),
				  Tables\Columns\TextColumn::make('created_at')
				  ->label('Created')
				  ->dateTime('d M Y')
				  ->sortable()
				  ->toggleable(isToggledHiddenByDefault: true),
		])
		->filters([
			Tables\Filters\SelectFilter::make('type')
			->options([
				'building' => 'Building / Structures',
			 'construction' => 'Construction',
			 'dam' => 'Dam / Water Works',
			 'road' => 'Road Works',
			 'fencing' => 'Fencing / Paddocking',
			 'renovation' => 'Renovation',
			 'repair' => 'Repair / Maintenance',
			 'electrical' => 'Electrical Works',
			 'plumbing' => 'Plumbing / Water Lines',
			 'security' => 'Security / CCTV',
			 'land_preparation' => 'Land Preparation',
			 'other' => 'Other',
			]),
			Tables\Filters\TernaryFilter::make('is_active')
			->label('Active Status'),
		])
		->actions([
			Tables\Actions\ViewAction::make()
			->label('')
			->tooltip('View')
			->iconButton(),
				  Tables\Actions\EditAction::make()
				  ->label('')
				  ->tooltip('Edit')
				  ->iconButton(),
				  Tables\Actions\DeleteAction::make()
				  ->label('')
				  ->tooltip('Delete')
				  ->iconButton(),
		])
		->bulkActions([
			Tables\Actions\BulkActionGroup::make([
				Tables\Actions\DeleteBulkAction::make(),
												 Tables\Actions\RestoreBulkAction::make(),
			]),
		]);
	}
	
	public static function getPages(): array
	{
		return [
			'index' => Pages\ListProjectCategories::route('/'),
			'create' => Pages\CreateProjectCategory::route('/create'),
			'edit' => Pages\EditProjectCategory::route('/{record}/edit'),
			'view' => Pages\ViewProjectCategory::route('/{record}'),
		];
	}
}