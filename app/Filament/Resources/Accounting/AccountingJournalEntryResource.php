<?php

namespace App\Filament\Resources\Accounting;


use App\Filament\Resources\Accounting\AccountingJournalEntryResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingCostCenter;
use App\Models\Accounting\AccountingProjectFund;
use App\Models\Accounting\AccountingJournalEntry;
use App\Services\Accounting\AccountingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccountingJournalEntryResource extends Resource
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
        return auth()->user()?->can('view accounting journal entries') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting journal entries') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view accounting journal entries') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting journal entries') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting journal entries') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete accounting journal entries') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete accounting journal entries') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore accounting journal entries') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore accounting journal entries') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete accounting journal entries') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete accounting journal entries') ?? false;
    }
	protected static ?string $model = AccountingJournalEntry::class;
	protected static ?string $navigationIcon = 'heroicon-o-document-text';
	protected static ?string $navigationGroup = 'Accounting';
	protected static ?string $navigationLabel = 'Journal Entries';
	protected static ?int $navigationSort = 3;
	
	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\Section::make('Journal Header')
			->columns(3)
			->schema([
				Forms\Components\TextInput::make('journal_number')
				->disabled()
				->dehydrated(false)
				->placeholder('Auto generated'),
					 Forms\Components\DatePicker::make('transaction_date')
					 ->required()
					 ->default(now()),
					 Forms\Components\TextInput::make('reference')
					 ->maxLength(100),
					 Forms\Components\Textarea::make('narration')
					 ->required()
					 ->columnSpanFull(),
					 Forms\Components\TextInput::make('source_type')
					 ->maxLength(100)
					 ->placeholder('manual, sale, purchase, payroll, asset...'),
					 Forms\Components\TextInput::make('source_id')
					 ->numeric(),
			]),
			
			Forms\Components\Section::make('Debit & Credit Lines')
			->description('The journal must balance before it can be posted.')
			->schema([
				Forms\Components\Repeater::make('lines')
				->schema([
					Forms\Components\Select::make('account_id')
					->label('Account')
					->options(fn () => AccountingAccount::query()->active()->orderBy('code')->get()->mapWithKeys(fn ($account) => [$account->id => $account->code . ' - ' . $account->name])->toArray())
					->searchable()
					->required(),
						 Forms\Components\TextInput::make('debit')
						 ->numeric()
						 ->default(0)
						 ->prefix('KES'),
						 Forms\Components\TextInput::make('credit')
						 ->numeric()
						 ->default(0)
						 ->prefix('KES'),
						 Forms\Components\Select::make('cost_center_id')
						 ->label('Cost Center')
						 ->options(fn () => AccountingCostCenter::query()->where('is_active', true)->orderBy('code')->get()->mapWithKeys(fn ($center) => [$center->id => $center->code . ' - ' . $center->name])->toArray())
						 ->searchable(),
						 Forms\Components\Select::make('project_fund_id')
						 ->label('Project Fund')
						 ->options(fn () => AccountingProjectFund::query()->orderBy('fund_code')->get()->mapWithKeys(fn ($fund) => [$fund->id => $fund->fund_code . ' - ' . $fund->name])->toArray())
						 ->searchable(),
						 Forms\Components\TextInput::make('description')
						 ->columnSpanFull(),
				])
				->columns(3)
				->minItems(2)
				->required()
				->addActionLabel('Add Journal Line'),
			]),
		]);
	}
	
	public static function table(Table $table): Table
	{
		return $table
		->defaultSort('transaction_date', 'desc')
		->columns([
			Tables\Columns\TextColumn::make('journal_number')->searchable()->sortable()->weight('bold'),
				  Tables\Columns\TextColumn::make('transaction_date')->date()->sortable(),
				  Tables\Columns\TextColumn::make('reference')->searchable()->toggleable(),
				  Tables\Columns\TextColumn::make('narration')->limit(45)->searchable(),
				  Tables\Columns\TextColumn::make('status')->badge(),
				  Tables\Columns\TextColumn::make('total_debit')->money('KES')->sortable(),
				  Tables\Columns\TextColumn::make('total_credit')->money('KES')->sortable(),
				  Tables\Columns\TextColumn::make('source_type')->badge()->toggleable(isToggledHiddenByDefault: true),
		])
		->filters([
			Tables\Filters\SelectFilter::make('status')->options([
				'draft' => 'Draft',
				'posted' => 'Posted',
				'reversed' => 'Reversed',
			]),
			Tables\Filters\Filter::make('transaction_date')
			->form([
				Forms\Components\DatePicker::make('from'),
				   Forms\Components\DatePicker::make('to'),
			])
			->query(function (Builder $query, array $data): Builder {
				return $query
				->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('transaction_date', '>=', $date))
				->when($data['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('transaction_date', '<=', $date));
			}),
		])
		->actions([
			Tables\Actions\ViewAction::make(),
				  Tables\Actions\EditAction::make()->visible(fn (AccountingJournalEntry $record): bool =>
                      $record->status === 'draft'
                      && (auth()->user()?->can('edit accounting journal entries') ?? false)
                  ),
				  Tables\Actions\Action::make('post')
				  ->icon('heroicon-o-check-circle')
				  ->color('success')
				  ->requiresConfirmation()
				  ->visible(fn (AccountingJournalEntry $record): bool =>
                      $record->status === 'draft'
                      && (auth()->user()?->can('post accounting journal entries') ?? false)
                  )
				  ->action(function (AccountingJournalEntry $record): void {
					  app(AccountingService::class)->postJournalEntry($record);
					  Notification::make()->title('Journal posted successfully')->success()->send();
				  }),
			Tables\Actions\Action::make('reverse')
			->icon('heroicon-o-arrow-uturn-left')
			->color('danger')
			->requiresConfirmation()
			->visible(fn (AccountingJournalEntry $record): bool =>
                $record->status === 'posted'
                && (auth()->user()?->can('reverse accounting journal entries') ?? false)
            )
			->action(function (AccountingJournalEntry $record): void {
				app(AccountingService::class)->reverseJournalEntry($record, 'Reversed from Filament');
				Notification::make()->title('Journal reversed successfully')->success()->send();
			}),
		]);
	}
	
	public static function getPages(): array
	{
		return [
			'index' => Pages\ListAccountingJournalEntries::route('/'),
			'create' => Pages\CreateAccountingJournalEntry::route('/create'),
			'view' => Pages\ViewAccountingJournalEntry::route('/{record}'),
			'edit' => Pages\EditAccountingJournalEntry::route('/{record}/edit'),
		];
	}
}