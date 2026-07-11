<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingJournalEntryResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Accounting\AccountingCostCenter;
use App\Models\Accounting\AccountingJournalEntry;
use App\Models\Accounting\AccountingProjectFund;
use App\Services\Accounting\AccountingBulkExportService;
use App\Services\Accounting\AccountingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingJournalEntryResource extends Resource
{
    protected static ?string $model = AccountingJournalEntry::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Accounting';
    protected static ?string $navigationLabel = 'Journal Entries';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view accounting journal entries') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view accounting journal entries') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting journal entries') ?? false;
    }

    public static function canEdit($record): bool
    {
        return ($record?->isDraft() ?? true)
            && (auth()->user()?->can('edit accounting journal entries') ?? false);
    }

    public static function canDelete($record): bool
    {
        return ($record?->canBeDeletedSafely() ?? false)
            && (auth()->user()?->can('delete accounting journal entries') ?? false);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Journal Header')
                ->description(
                    'Manual journals require approval before posting. '
                    . 'Posted journals cannot be edited or deleted; use reversal.'
                )
                ->icon('heroicon-o-document-text')
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    Forms\Components\TextInput::make('journal_number')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Auto-generated'),

                    Forms\Components\DatePicker::make('transaction_date')
                        ->required()
                        ->default(now('Africa/Nairobi'))
                        ->native(false),

                    Forms\Components\TextInput::make('reference')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('currency_code')
                        ->default('KES')
                        ->maxLength(3)
                        ->required(),

                    Forms\Components\Textarea::make('narration')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('source_type')
                        ->default('manual'),
                ]),

            Forms\Components\Section::make('Debit & Credit Lines')
                ->description(
                    'Each line must have either a debit or a credit. '
                    . 'The complete journal must balance exactly.'
                )
                ->icon('heroicon-o-scale')
                ->schema([
                    Forms\Components\Repeater::make('lines')
                        ->schema([
                            Forms\Components\Select::make('account_id')
                                ->label('Postable Account')
                                ->options(fn (): array =>
                                    AccountingAccount::query()
                                        ->postable()
                                        ->where('allow_manual_posting', true)
                                        ->orderBy('code')
                                        ->get()
                                        ->mapWithKeys(fn ($account): array => [
                                            $account->id =>
                                                $account->code
                                                . ' - '
                                                . $account->name,
                                        ])
                                        ->all()
                                )
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required()
                                ->columnSpan(['default' => 1, 'xl' => 4]),

                            Forms\Components\TextInput::make('debit')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefix('KES')
                                ->columnSpan(['default' => 1, 'md' => 1, 'xl' => 2]),

                            Forms\Components\TextInput::make('credit')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefix('KES')
                                ->columnSpan(['default' => 1, 'md' => 1, 'xl' => 2]),

                            Forms\Components\Select::make('cost_center_id')
                                ->label('Cost Centre')
                                ->options(fn (): array =>
                                    AccountingCostCenter::query()
                                        ->where('is_active', true)
                                        ->orderBy('code')
                                        ->get()
                                        ->mapWithKeys(fn ($center): array => [
                                            $center->id =>
                                                $center->code
                                                . ' - '
                                                . $center->name,
                                        ])
                                        ->all()
                                )
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->columnSpan(['default' => 1, 'xl' => 2]),

                            Forms\Components\Select::make('project_fund_id')
                                ->label('Project Fund')
                                ->options(fn (): array =>
                                    AccountingProjectFund::query()
                                        ->orderBy('fund_code')
                                        ->get()
                                        ->mapWithKeys(fn ($fund): array => [
                                            $fund->id =>
                                                $fund->fund_code
                                                . ' - '
                                                . $fund->name,
                                        ])
                                        ->all()
                                )
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->columnSpan(['default' => 1, 'xl' => 2]),

                            Forms\Components\TextInput::make('party_name')
                                ->label('Party Name')
                                ->columnSpan(['default' => 1, 'xl' => 2]),

                            Forms\Components\TextInput::make('party_pin')
                                ->label('KRA PIN')
                                ->columnSpan(['default' => 1, 'xl' => 2]),

                            Forms\Components\TextInput::make('tax_code')
                                ->label('Tax Code')
                                ->columnSpan(['default' => 1, 'xl' => 2]),

                            Forms\Components\TextInput::make('etims_document_number')
                                ->label('eTIMS Document')
                                ->columnSpan(['default' => 1, 'xl' => 2]),

                            Forms\Components\TextInput::make('description')
                                ->columnSpanFull(),
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 12,
                        ])
                        ->minItems(2)
                        ->required()
                        ->collapsible()
                        ->addActionLabel('Add Journal Line'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('transaction_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('journal_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record): ?string => $record->source_reference),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('narration')
                    ->limit(55)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'posted' => 'success',
                        'reversed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('approved_at')
                    ->label('Approved')
                    ->getStateUsing(
                        fn ($record): bool => filled($record->approved_at)
                    )
                    ->boolean(),

                Tables\Columns\TextColumn::make('total_debit')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_credit')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('source_type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Archived')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Active')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'reversed' => 'Reversed',
                    ]),

                Tables\Filters\SelectFilter::make('source_type')
                    ->options(fn (): array =>
                        AccountingJournalEntry::query()
                            ->whereNotNull('source_type')
                            ->distinct()
                            ->orderBy('source_type')
                            ->pluck('source_type', 'source_type')
                            ->all()
                    )
                    ->searchable(),

                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->native(false),
                        Forms\Components\DatePicker::make('to')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder =>
                                    $query->whereDate('transaction_date', '>=', $date)
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $query, $date): Builder =>
                                    $query->whereDate('transaction_date', '<=', $date)
                            )
                    ),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (AccountingJournalEntry $record): bool =>
                        static::canEdit($record)
                    ),

                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Approval Notes')
                            ->rows(3),
                    ])
                    ->visible(fn (AccountingJournalEntry $record): bool =>
                        $record->isDraft()
                        && ! $record->isApproved()
                        && (auth()->user()?->can('approve accounting journal entries') ?? false)
                    )
                    ->action(function (AccountingJournalEntry $record, array $data): void {
                        app(AccountingService::class)->approveJournalEntry(
                            $record,
                            $data['approval_notes'] ?? null
                        );
                        Notification::make()->success()->title('Journal approved')->send();
                    }),

                Tables\Actions\Action::make('post')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (AccountingJournalEntry $record): bool =>
                        $record->isDraft()
                        && (auth()->user()?->can('post accounting journal entries') ?? false)
                    )
                    ->action(function (AccountingJournalEntry $record): void {
                        app(AccountingService::class)->postJournalEntry($record);
                        Notification::make()->success()->title('Journal posted')->send();
                    }),

                Tables\Actions\Action::make('reverse')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->rows(3),
                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('Reversal Date')
                            ->default(now('Africa/Nairobi'))
                            ->native(false)
                            ->required(),
                    ])
                    ->visible(fn (AccountingJournalEntry $record): bool =>
                        $record->isPosted()
                        && (auth()->user()?->can('reverse accounting journal entries') ?? false)
                    )
                    ->action(function (AccountingJournalEntry $record, array $data): void {
                        app(AccountingService::class)->reverseJournalEntry(
                            $record,
                            $data['reason'],
                            $data['transaction_date']
                        );
                        Notification::make()->success()->title('Journal reversed')->send();
                    }),

                Tables\Actions\Action::make('deleteDraft')
                    ->label('Delete Draft')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (AccountingJournalEntry $record): bool => static::canDelete($record))
                    ->action(function (AccountingJournalEntry $record): void {
                        app(AccountingService::class)->deleteDraftJournal($record);
                        Notification::make()->success()->title('Draft deleted')->send();
                    }),

                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approveSelected')
                        ->label('Approve Selected Drafts')
                        ->icon('heroicon-o-shield-check')
                        ->color('info')
                        ->form([
                            Forms\Components\Textarea::make('approval_notes')
                                ->label('Approval Notes')
                                ->rows(3),
                        ])
                        ->visible(fn (): bool =>
                            auth()->user()?->can('approve accounting journal entries') ?? false
                        )
                        ->action(function (Collection $records, array $data): void {
                            $done = 0;
                            foreach ($records as $record) {
                                if ($record->isDraft() && ! $record->isApproved()) {
                                    app(AccountingService::class)->approveJournalEntry(
                                        $record,
                                        $data['approval_notes'] ?? null
                                    );
                                    $done++;
                                }
                            }
                            Notification::make()->success()->title("{$done} journal(s) approved")->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('postSelected')
                        ->label('Post Selected Drafts')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (): bool =>
                            auth()->user()?->can('post accounting journal entries') ?? false
                        )
                        ->action(function (Collection $records): void {
                            $done = 0;
                            $skipped = 0;
                            foreach ($records as $record) {
                                if (! $record->isDraft()) {
                                    $skipped++;
                                    continue;
                                }
                                try {
                                    app(AccountingService::class)->postJournalEntry($record);
                                    $done++;
                                } catch (\Throwable) {
                                    $skipped++;
                                }
                            }
                            Notification::make()
                                ->title("{$done} journal(s) posted")
                                ->body("{$skipped} journal(s) skipped or failed validation.")
                                ->color($skipped > 0 ? 'warning' : 'success')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('reverseSelected')
                        ->label('Reverse Selected Posted')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->required()
                                ->rows(3),
                            Forms\Components\DatePicker::make('transaction_date')
                                ->default(now('Africa/Nairobi'))
                                ->native(false)
                                ->required(),
                        ])
                        ->visible(fn (): bool =>
                            auth()->user()?->can('reverse accounting journal entries') ?? false
                        )
                        ->action(function (Collection $records, array $data): void {
                            $done = 0;
                            foreach ($records as $record) {
                                if ($record->isPosted()) {
                                    app(AccountingService::class)->reverseJournalEntry(
                                        $record,
                                        $data['reason'],
                                        $data['transaction_date']
                                    );
                                    $done++;
                                }
                            }
                            Notification::make()->success()->title("{$done} journal(s) reversed")->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deleteDrafts')
                        ->label('Delete Eligible Drafts')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $done = 0;
                            $skipped = 0;
                            foreach ($records as $record) {
                                if ($record->canBeDeletedSafely()) {
                                    app(AccountingService::class)->deleteDraftJournal($record);
                                    $done++;
                                } else {
                                    $skipped++;
                                }
                            }
                            Notification::make()
                                ->title("{$done} draft(s) deleted")
                                ->body("{$skipped} posted, reversed, or source-linked journal(s) skipped.")
                                ->color($skipped > 0 ? 'warning' : 'success')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\RestoreBulkAction::make(),

                    Tables\Actions\BulkAction::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(fn (Collection $records) =>
                            app(AccountingBulkExportService::class)->csv(
                                $records,
                                [
                                    'Journal Number' => 'journal_number',
                                    'Date' => fn ($record) => $record->transaction_date?->format('Y-m-d'),
                                    'Reference' => 'reference',
                                    'Narration' => 'narration',
                                    'Status' => 'status',
                                    'Debit' => 'total_debit',
                                    'Credit' => 'total_credit',
                                    'Source Type' => 'source_type',
                                    'Source ID' => 'source_id',
                                ],
                                'journal-entries-' . now()->format('Ymd_His') . '.csv'
                            )
                        ),
                ]),
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
