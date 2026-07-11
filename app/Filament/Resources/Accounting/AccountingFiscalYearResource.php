<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingFiscalYearResource\Pages;
use App\Models\Accounting\AccountingFiscalYear;
use App\Services\Accounting\AccountingBulkExportService;
use App\Services\Accounting\AccountingPeriodClosingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AccountingFiscalYearResource extends Resource
{
    protected static ?string $model = AccountingFiscalYear::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Accounting Setup';
    protected static ?string $navigationLabel = 'Fiscal Years';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view accounting fiscal years') ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
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
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Fiscal Year Control')
                ->description('Define reporting boundaries. Close and lock years using controlled actions, not direct status editing.')
                ->icon('heroicon-o-calendar-days')
                ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()->maxLength(100)->unique(ignoreRecord: true),
                    Forms\Components\DatePicker::make('start_date')
                        ->native(false)->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->native(false)->required()->after('start_date'),
                    Forms\Components\Toggle::make('is_current')
                        ->label('Current Fiscal Year'),
                    Forms\Components\Select::make('status')
                        ->native(false)->required()->default('draft')
                        ->options([
                            'draft' => 'Draft',
                            'open' => 'Open',
                            'closed' => 'Closed',
                            'locked' => 'Locked',
                        ])
                        ->disabled(fn (?AccountingFiscalYear $record): bool => filled($record)),
                    Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('start_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'open' => 'success', 'closed' => 'warning', 'locked' => 'danger', default => 'gray',
                }),
                Tables\Columns\IconColumn::make('is_current')->boolean(),
                Tables\Columns\TextColumn::make('periods_count')->counts('periods')->label('Periods')->badge(),
                Tables\Columns\TextColumn::make('journal_entries_count')->counts('journalEntries')->label('Journals')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed', 'locked' => 'Locked',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn (AccountingFiscalYear $record): bool => in_array($record->status, ['draft', 'open'], true)),
                Tables\Actions\Action::make('close')->icon('heroicon-o-lock-closed')->color('warning')
                    ->requiresConfirmation()->visible(fn (AccountingFiscalYear $record): bool => $record->status === 'open')
                    ->action(fn (AccountingFiscalYear $record) => app(AccountingPeriodClosingService::class)->closeFiscalYear($record)),
                Tables\Actions\Action::make('lock')->icon('heroicon-o-shield-check')->color('danger')
                    ->requiresConfirmation()->visible(fn (AccountingFiscalYear $record): bool => $record->status !== 'locked')
                    ->action(fn (AccountingFiscalYear $record) => app(AccountingPeriodClosingService::class)->lockFiscalYear($record)),
                Tables\Actions\Action::make('reopen')->icon('heroicon-o-lock-open')->color('success')
                    ->requiresConfirmation()->visible(fn (AccountingFiscalYear $record): bool => in_array($record->status, ['closed', 'locked'], true))
                    ->action(fn (AccountingFiscalYear $record) => app(AccountingPeriodClosingService::class)->reopenFiscalYear($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('closeSelected')->label('Close Selected')->icon('heroicon-o-lock-closed')->color('warning')
                    ->requiresConfirmation()->action(function (Collection $records): void {
                        $done = 0;
                        foreach ($records as $record) {
                            if ($record->status === 'open') {
                                app(AccountingPeriodClosingService::class)->closeFiscalYear($record); $done++;
                            }
                        }
                        Notification::make()->success()->title("{$done} fiscal year(s) closed")->send();
                    })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('lockSelected')->label('Lock Selected')->icon('heroicon-o-shield-check')->color('danger')
                    ->requiresConfirmation()->action(function (Collection $records): void {
                        $done = 0;
                        foreach ($records as $record) {
                            if ($record->status !== 'locked') {
                                app(AccountingPeriodClosingService::class)->lockFiscalYear($record); $done++;
                            }
                        }
                        Notification::make()->success()->title("{$done} fiscal year(s) locked")->send();
                    })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('reopenSelected')->label('Reopen Selected')->icon('heroicon-o-lock-open')->color('success')
                    ->requiresConfirmation()->action(function (Collection $records): void {
                        foreach ($records as $record) app(AccountingPeriodClosingService::class)->reopenFiscalYear($record);
                        Notification::make()->success()->title('Selected fiscal years reopened')->send();
                    })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn (Collection $records) => app(AccountingBulkExportService::class)->csv($records, [
                        'Name' => 'name', 'Start' => fn ($r) => $r->start_date?->format('Y-m-d'),
                        'End' => fn ($r) => $r->end_date?->format('Y-m-d'), 'Status' => 'status', 'Current' => 'is_current',
                    ], 'fiscal-years-' . now()->format('Ymd_His') . '.csv')),
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
