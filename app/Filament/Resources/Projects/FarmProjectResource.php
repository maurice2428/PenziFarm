<?php

namespace App\Filament\Resources\Projects;


use App\Filament\Resources\Projects\FarmProjectResource\Pages;
use App\Filament\Resources\Projects\FarmProjectResource\RelationManagers;
use App\Models\Projects\FarmProject;
use App\Models\Projects\ProjectCategory;
use App\Models\User;
use App\Services\Projects\ProjectFinancialService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FarmProjectResource extends Resource
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
        return auth()->user()?->can('view projects') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view projects') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view projects') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create projects') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit projects') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete projects') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete projects') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore projects') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore projects') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete projects') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete projects') ?? false;
    }
	protected static ?string $model = FarmProject::class;
	
	protected static ?string $navigationGroup = 'Projects & Works';
	
	protected static ?string $navigationLabel = 'Projects';
	
	protected static ?string $modelLabel = 'Project';
	
	protected static ?string $pluralModelLabel = 'Projects';
	
	protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
	
	protected static ?int $navigationSort = 2;
	
	protected static ?string $slug = 'projects/works';
	
	
	public static function form(Form $form): Form
	{
		return $form
		->schema([
			Forms\Components\Section::make('Project Identity')
			->description('Register buildings, roads, dams, fencing, paddocks, renovations, repairs, and farm works.')
			->icon('heroicon-o-building-office-2')
			->columns(12)
			->schema([
				Forms\Components\TextInput::make('project_number')
				->label('Project No.')
				->default(fn() => 'PRJ-' . now('Africa/Nairobi')->format('Ymd-His'))
				->required()
				->unique(ignoreRecord: true)
				->maxLength(80)
				->columnSpan(3),
					 Forms\Components\TextInput::make('name')
					 ->label('Project Name')
					 ->required()
					 ->maxLength(255)
					 ->columnSpan(5),
					 Forms\Components\Select::make('project_category_id')
					 ->label('Category')
					 ->options(fn(): array => ProjectCategory::query()
					 ->where('is_active', true)
					 ->orderBy('name')
					 ->pluck('name', 'id')
					 ->toArray())
					 ->searchable()
					 ->preload()
					 ->columnSpan(4),
					 Forms\Components\Select::make('project_type')
					 ->label('Project Type')
					 ->options(self::projectTypeOptions())
					 ->default('other')
					 ->searchable()
					 ->required()
					 ->columnSpan(3),
					 Forms\Components\Select::make('priority')
					 ->label('Priority')
					 ->options([
						 'low' => 'Low',
				'medium' => 'Medium',
				'high' => 'High',
				'urgent' => 'Urgent',
					 ])
					 ->default('medium')
					 ->required()
					 ->columnSpan(2),
					 Forms\Components\Select::make('status')
					 ->label('Status')
					 ->options(self::statusOptions())
					 ->default('planned')
					 ->required()
					 ->columnSpan(3),
					 Forms\Components\TextInput::make('location')
					 ->label('Location')
					 ->placeholder('e.g. Kambi Moto, Lower paddock, Borehole side')
					 ->maxLength(255)
					 ->columnSpan(4),
					 Forms\Components\TextInput::make('land_area')
					 ->label('Land Area')
					 ->numeric()
					 ->minValue(0)
					 ->columnSpan(2),
					 Forms\Components\Select::make('land_area_unit')
					 ->label('Area Unit')
					 ->options([
						 'acres' => 'Acres',
						 'hectares' => 'Hectares',
						 'sqm' => 'Square Metres',
						 'feet' => 'Feet',
						 'meters' => 'Metres',
						 'km' => 'Kilometres',
					 ])
					 ->default('acres')
					 ->columnSpan(2),
			]),
		   Forms\Components\Section::make('Scope & Planning')
		   ->icon('heroicon-o-document-text')
		   ->columns(12)
		   ->schema([
			   Forms\Components\Textarea::make('description')
			   ->label('Description')
			   ->rows(3)
			   ->columnSpanFull(),
					Forms\Components\Textarea::make('objectives')
					->label('Objectives')
					->rows(3)
					->columnSpan(6),
					Forms\Components\Textarea::make('scope_of_work')
					->label('Scope of Work')
					->rows(3)
					->columnSpan(6),
					Forms\Components\DatePicker::make('start_date')
					->label('Start Date')
					->native(false)
					->columnSpan(3),
					Forms\Components\DatePicker::make('expected_end_date')
					->label('Expected End Date')
					->native(false)
					->columnSpan(3),
					Forms\Components\DatePicker::make('actual_end_date')
					->label('Actual End Date')
					->native(false)
					->columnSpan(3),
					Forms\Components\TextInput::make('progress_percent')
					->label('Progress %')
					->numeric()
					->minValue(0)
					->maxValue(100)
					->suffix('%')
					->default(0)
					->columnSpan(3),
		   ]),
		   Forms\Components\Section::make('Budget Control')
		   ->icon('heroicon-o-banknotes')
		   ->description('These totals can be recalculated from project budget lines and expenses.')
		   ->columns(12)
		   ->schema([
			   Forms\Components\TextInput::make('budget_amount')
			   ->label('Estimated Budget')
			   ->numeric()
			   ->prefix('KES')
			   ->default(0)
			   ->columnSpan(3),
					Forms\Components\TextInput::make('approved_budget_amount')
					->label('Approved Budget')
					->numeric()
					->prefix('KES')
					->default(0)
					->columnSpan(3),
					Forms\Components\TextInput::make('committed_amount')
					->label('Committed')
					->numeric()
					->prefix('KES')
					->default(0)
					->disabled()
					->dehydrated()
					->columnSpan(2),
					Forms\Components\TextInput::make('spent_amount')
					->label('Spent')
					->numeric()
					->prefix('KES')
					->default(0)
					->disabled()
					->dehydrated()
					->columnSpan(2),
					Forms\Components\TextInput::make('balance_amount')
					->label('Balance')
					->numeric()
					->prefix('KES')
					->default(0)
					->disabled()
					->dehydrated()
					->columnSpan(2),
		   ]),
		   Forms\Components\Section::make('Contractor & Responsibility')
		   ->icon('heroicon-o-user-group')
		   ->columns(12)
		   ->schema([
			   Forms\Components\TextInput::make('contractor_name')
			   ->label('Contractor / Supplier')
			   ->maxLength(255)
			   ->columnSpan(4),
					Forms\Components\TextInput::make('contractor_phone')
					->label('Contractor Phone')
					->tel()
					->maxLength(50)
					->columnSpan(3),
					Forms\Components\TextInput::make('contractor_email')
					->label('Contractor Email')
					->email()
					->maxLength(255)
					->columnSpan(3),
					Forms\Components\Select::make('manager_id')
					->label('Project Manager')
					->options(fn(): array => User::query()
					->orderBy('name')
					->pluck('name', 'id')
					->toArray())
					->searchable()
					->preload()
					->columnSpan(2),
					Forms\Components\Textarea::make('notes')
					->label('Internal Notes')
					->rows(3)
					->columnSpanFull(),
					Forms\Components\Hidden::make('created_by')
					->default(fn() => auth()->id()),
		   ]),
		]);
	}
	
	public static function table(Table $table): Table
	{
		return $table
		->defaultSort('created_at', 'desc')
		->poll('30s')
		->columns([
			Tables\Columns\TextColumn::make('project_number')
			->label('Project No.')
			->searchable()
			->sortable()
			->badge()
			->color('gray'),
				  Tables\Columns\TextColumn::make('name')
				  ->label('Project')
				  ->searchable()
				  ->sortable()
				  ->weight(FontWeight::Bold)
				  ->description(fn(FarmProject $record): string => $record->location ?: 'No location'),
				  Tables\Columns\TextColumn::make('project_type')
				  ->label('Type')
				  ->badge()
				  ->formatStateUsing(fn(?string $state): string => str($state ?: 'other')->replace('_', ' ')->headline())
				  ->color('info'),
				  Tables\Columns\TextColumn::make('status_label')
				  ->label('Status')
				  ->badge()
				  ->color(fn(FarmProject $record): string => $record->status_color)
				  ->sortable(query: fn(Builder $query, string $direction): Builder => $query->orderBy('status', $direction)),
				  Tables\Columns\TextColumn::make('priority_label')
				  ->label('Priority')
				  ->badge()
				  ->color(fn(FarmProject $record): string => match ($record->priority) {
					  'urgent' => 'danger',
					  'high' => 'warning',
					  'medium' => 'info',
					  'low' => 'gray',
					  default => 'gray',
				  }),
			Tables\Columns\TextColumn::make('progress_percent')
			->label('Progress')
			->suffix('%')
			->badge()
			->color(fn(FarmProject $record): string => match (true) {
				$record->progress_percent >= 90 => 'success',
				$record->progress_percent >= 50 => 'info',
				$record->progress_percent >= 20 => 'warning',
				default => 'gray',
			})
			->sortable(),
				  Tables\Columns\TextColumn::make('approved_budget_amount')
				  ->label('Budget')
				  ->money('KES')
				  ->sortable(),
				  Tables\Columns\TextColumn::make('spent_amount')
				  ->label('Spent')
				  ->money('KES')
				  ->sortable()
				  ->color(fn(FarmProject $record): string => $record->is_over_budget ? 'danger' : 'success'),
				  Tables\Columns\TextColumn::make('balance_amount')
				  ->label('Balance')
				  ->money('KES')
				  ->sortable()
				  ->color(fn(FarmProject $record): string => (float) $record->balance_amount < 0 ? 'danger' : 'gray'),
				  Tables\Columns\TextColumn::make('expected_end_date')
				  ->label('Expected End')
				  ->date('d M Y')
				  ->sortable()
				  ->toggleable(),
				  Tables\Columns\TextColumn::make('manager.name')
				  ->label('Manager')
				  ->toggleable(isToggledHiddenByDefault: true),
				  Tables\Columns\TextColumn::make('contractor_name')
				  ->label('Contractor')
				  ->searchable()
				  ->toggleable(isToggledHiddenByDefault: true),
		])
		->filters([
			Tables\Filters\SelectFilter::make('project_type')
			->label('Type')
			->options(self::projectTypeOptions()),
				  Tables\Filters\SelectFilter::make('status')
				  ->options(self::statusOptions()),
				  Tables\Filters\SelectFilter::make('priority')
				  ->options([
					  'low' => 'Low',
				'medium' => 'Medium',
				'high' => 'High',
				'urgent' => 'Urgent',
				  ]),
			Tables\Filters\SelectFilter::make('project_category_id')
			->label('Category')
			->options(fn(): array => ProjectCategory::query()
			->orderBy('name')
			->pluck('name', 'id')
			->toArray())
			->searchable(),
				  Tables\Filters\Filter::make('over_budget')
				  ->label('Over Budget')
				  ->query(fn(Builder $query): Builder => $query->whereColumn('spent_amount', '>', 'approved_budget_amount')),
				  Tables\Filters\Filter::make('delayed')
				  ->label('Delayed')
				  ->query(fn(Builder $query): Builder => $query
				  ->whereNotNull('expected_end_date')
				  ->whereDate('expected_end_date', '<', now('Africa/Nairobi'))
				  ->whereNotIn('status', ['completed', 'closed', 'cancelled'])),
		])
		->actions([
			Tables\Actions\Action::make('viewProject')
            ->visible(fn (): bool => auth()->user()?->can('view projects') ?? false)
			->label('')
			->tooltip('View')
			->icon('heroicon-o-eye')
			->iconButton()
			->color('gray')
			->url(fn(FarmProject $record): string => static::getUrl('view', ['record' => $record])),
				  Tables\Actions\Action::make('printProjectReport')
                  ->visible(fn (): bool => auth()->user()?->can('print project reports') ?? false)
				  ->label('')
				  ->tooltip('Print Project Report')
				  ->icon('heroicon-o-printer')
				  ->iconButton()
				  ->color('info')
				  ->openUrlInNewTab()
				  ->url(fn(FarmProject $record): string => route('projects.reports.detail', $record)),
				  Tables\Actions\EditAction::make()
				  ->label('')
				  ->tooltip('Edit')
				  ->iconButton(),
				  Tables\Actions\Action::make('approve')
				  ->label('')
				  ->tooltip('Approve Project')
				  ->icon('heroicon-o-check-badge')
				  ->iconButton()
				  ->color('success')
				  ->requiresConfirmation()
				  ->visible(fn(FarmProject $record): bool =>
                      in_array($record->status, ['planned', 'on_hold'], true)
                      && (auth()->user()?->can('edit projects') ?? false)
                  )
				  ->action(function (FarmProject $record): void {
					  $record->forceFill([
						  'status' => 'approved',
						  'approved_by' => auth()->id(),
										 'approved_at' => now(),
					  ])->save();
					  
					  Notification::make()
					  ->title('Project approved')
					  ->body($record->name . ' has been approved.')
					  ->success()
					  ->send();
				  }),
			Tables\Actions\Action::make('recalculate')
            ->visible(fn (): bool => auth()->user()?->can('edit projects') ?? false)
			->label('')
			->tooltip('Recalculate Financials')
			->icon('heroicon-o-calculator')
			->iconButton()
			->color('info')
			->action(function (FarmProject $record): void {
				app(ProjectFinancialService::class)->recalculate($record);
				app(ProjectFinancialService::class)->recalculateProgress($record);
				
				Notification::make()
				->title('Project recalculated')
				->body('Budget, spent, balance and progress values have been refreshed.')
				->success()
				->send();
			}),
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
		])
		->emptyStateIcon('heroicon-o-building-office-2')
		->emptyStateHeading('No projects created yet')
		->emptyStateDescription('Create your first building, road, dam, fencing, paddock, renovation, or construction project.');
	}
	
	public static function getRelations(): array
	{
		return [
			RelationManagers\ProjectMilestonesRelationManager::class,
			RelationManagers\ProjectTasksRelationManager::class,
			RelationManagers\ProjectBudgetLinesRelationManager::class,
			RelationManagers\ProjectExpensesRelationManager::class,
			RelationManagers\ProjectProgressUpdatesRelationManager::class,
			RelationManagers\ProjectDocumentsRelationManager::class,
		];
	}
	
	public static function getPages(): array
	{
		return [
			'index' => Pages\ListFarmProjects::route('/'),
			'create' => Pages\CreateFarmProject::route('/create'),
			'edit' => Pages\EditFarmProject::route('/{record}/edit'),
			'view' => Pages\ViewFarmProject::route('/{record}'),
		];
	}
	
	public static function projectTypeOptions(): array
	{
		return [
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
		];
	}
	
	public static function statusOptions(): array
	{
		return [
			'planned' => 'Planned',
			'approved' => 'Approved',
			'in_progress' => 'In Progress',
			'on_hold' => 'On Hold',
			'completed' => 'Completed',
			'closed' => 'Closed',
			'cancelled' => 'Cancelled',
		];
	}
}