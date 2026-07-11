<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingPeriodResource\Pages;
use App\Models\Accounting\AccountingPeriod;
use App\Services\Accounting\AccountingBulkExportService;
use App\Services\Accounting\AccountingPeriodClosingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AccountingPeriodResource extends Resource
{
    protected static ?string $model = AccountingPeriod::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Accounting Setup';
    protected static ?string $navigationLabel = 'Accounting Periods';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view accounting periods') ?? false;
    }

    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Accounting Period')
                ->icon('heroicon-o-calendar')->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->schema([
                    Forms\Components\Select::make('fiscal_year_id')->relationship('fiscalYear', 'name')->searchable()->preload()->required()->native(false),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('period_number')->numeric()->integer()->minValue(1)->maxValue(53)->required(),
                    Forms\Components\DatePicker::make('start_date')->native(false)->required(),
                    Forms\Components\DatePicker::make('end_date')->native(false)->required()->afterOrEqual('start_date'),
                    Forms\Components\Select::make('status')->native(false)->options([
                        'open' => 'Open', 'closed' => 'Closed', 'locked' => 'Locked',
                    ])->default('open')->required()->disabled(fn (?AccountingPeriod $record): bool => filled($record)),
                ]),
        ]);
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
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('start_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fiscalYear.name')->label('Fiscal Year')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('period_number')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('start_date')->date('d M Y'),
                Tables\Columns\TextColumn::make('end_date')->date('d M Y'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'open' => 'success', 'closed' => 'warning', 'locked' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('journal_entries_count')->counts('journalEntries')->label('Journals')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fiscal_year_id')->relationship('fiscalYear', 'name')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('status')->options(['open' => 'Open', 'closed' => 'Closed', 'locked' => 'Locked']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn (AccountingPeriod $record): bool => $record->status === 'open'),
                Tables\Actions\Action::make('close')->color('warning')->icon('heroicon-o-lock-closed')->requiresConfirmation()
                    ->visible(fn (AccountingPeriod $record): bool => $record->status === 'open')
                    ->action(fn (AccountingPeriod $record) => app(AccountingPeriodClosingService::class)->closePeriod($record)),
                Tables\Actions\Action::make('lock')->color('danger')->icon('heroicon-o-shield-check')->requiresConfirmation()
                    ->visible(fn (AccountingPeriod $record): bool => $record->status !== 'locked')
                    ->action(fn (AccountingPeriod $record) => app(AccountingPeriodClosingService::class)->lockPeriod($record)),
                Tables\Actions\Action::make('reopen')->color('success')->icon('heroicon-o-lock-open')->requiresConfirmation()
                    ->visible(fn (AccountingPeriod $record): bool => $record->status !== 'open')
                    ->action(fn (AccountingPeriod $record) => app(AccountingPeriodClosingService::class)->reopenPeriod($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('closeSelected')->label('Close Selected')->color('warning')->icon('heroicon-o-lock-closed')->requiresConfirmation()
                    ->action(function (Collection $records): void { foreach ($records as $r) if ($r->status === 'open') app(AccountingPeriodClosingService::class)->closePeriod($r); Notification::make()->success()->title('Eligible periods closed')->send(); })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('lockSelected')->label('Lock Selected')->color('danger')->icon('heroicon-o-shield-check')->requiresConfirmation()
                    ->action(function (Collection $records): void { foreach ($records as $r) if ($r->status !== 'locked') app(AccountingPeriodClosingService::class)->lockPeriod($r); Notification::make()->success()->title('Selected periods locked')->send(); })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('reopenSelected')->label('Reopen Selected')->color('success')->icon('heroicon-o-lock-open')->requiresConfirmation()
                    ->action(function (Collection $records): void { foreach ($records as $r) app(AccountingPeriodClosingService::class)->reopenPeriod($r); Notification::make()->success()->title('Selected periods reopened')->send(); })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn (Collection $records) => app(AccountingBulkExportService::class)->csv($records, [
                        'Fiscal Year' => fn ($r) => $r->fiscalYear?->name, 'Period' => 'name', 'Number' => 'period_number',
                        'Start' => fn ($r) => $r->start_date?->format('Y-m-d'), 'End' => fn ($r) => $r->end_date?->format('Y-m-d'), 'Status' => 'status',
                    ], 'accounting-periods-' . now()->format('Ymd_His') . '.csv')),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAccountingPeriods::route('/'), 'create' => Pages\CreateAccountingPeriod::route('/create'), 'edit' => Pages\EditAccountingPeriod::route('/{record}/edit')];
    }
}
