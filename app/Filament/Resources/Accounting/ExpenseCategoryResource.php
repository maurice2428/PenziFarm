<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Concerns\ChecksExplicitPermissions;
use App\Filament\Resources\Accounting\ExpenseCategoryResource\Pages;
use App\Models\Accounting\AccountingAccount;
use App\Models\Finance\ExpenseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ExpenseCategoryResource extends Resource
{
    use ChecksExplicitPermissions;

    protected static ?string $model = ExpenseCategory::class;
    protected static ?string $navigationGroup = 'Accounting';
    protected static ?string $navigationLabel = 'Expense Categories';
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?int $navigationSort = 31;

    public static function canViewAny(): bool
    {
        return static::permits('view expense categories');
    }

    public static function canCreate(): bool
    {
        return static::permits('create expense categories');
    }

    public static function canEdit($record): bool
    {
        return static::permits('edit expense categories');
    }

    public static function canDelete($record): bool
    {
        return static::permits('delete expense categories')
            && $record->canBeDeletedSafely();
    }

    public static function canRestore($record): bool
    {
        return static::permits('archive expense categories');
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Expense Category')
                ->description('Map each operational expense type to a postable general-ledger account and sensible default tax treatment.')
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()->maxLength(50)->unique(ignoreRecord: true)->prefixIcon('heroicon-o-hashtag'),
                    Forms\Components\TextInput::make('name')
                        ->required()->maxLength(255)->prefixIcon('heroicon-o-tag'),
                    Forms\Components\Select::make('account_id')
                        ->label('Expense Account')
                        ->options(fn (): array => AccountingAccount::query()
                            ->active()->postable()->whereIn('type', ['expense', 'cost_of_sales'])
                            ->orderBy('code')->get()
                            ->mapWithKeys(fn (AccountingAccount $a): array => [$a->id => "{$a->code} · {$a->name}"])->all())
                        ->searchable()->preload()->required()->native(false),
                    Forms\Components\Select::make('default_tax_treatment')
                        ->options([
                            'standard_vat' => 'Standard VAT',
                            'zero_rated' => 'Zero Rated',
                            'exempt' => 'VAT Exempt',
                            'non_vat' => 'Outside VAT / Non-VAT',
                        ])->default('non_vat')->required()->native(false),
                    Forms\Components\Select::make('default_wht_code')
                        ->label('Default WHT Type')
                        ->options([
                            'WHT_PROFESSIONAL' => 'Professional / Management / Training',
                            'WHT_RENT' => 'Commercial Rent',
                            'WHT_CONTRACTUAL' => 'Contractual Fees',
                        ])->searchable()->native(false),
                    Forms\Components\TextInput::make('default_wht_rate')
                        ->label('Default WHT Rate')->numeric()->suffix('%')->minValue(0)->maxValue(100)->default(0),
                    Forms\Components\Toggle::make('requires_etims')
                        ->label('Require eTIMS Evidence')->default(false),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')->default(true),
                    Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('code')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('account.code')->label('Account')->badge()->color('info'),
                Tables\Columns\TextColumn::make('account.name')->label('GL Account')->toggleable(),
                Tables\Columns\TextColumn::make('default_tax_treatment')->label('VAT')->badge()
                    ->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->title()),
                Tables\Columns\TextColumn::make('default_wht_rate')->label('WHT')->suffix('%'),
                Tables\Columns\IconColumn::make('requires_etims')->label('eTIMS')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active Status')->boolean(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (): bool => static::permits(
                            'edit expense categories'
                        )
                    )
                    ->slideOver()
                    ->modalWidth('5xl'),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (ExpenseCategory $record): bool =>
                            static::permits(
                                'delete expense categories'
                            )
                            && $record->canBeDeletedSafely()
                    ),
                Tables\Actions\RestoreAction::make()
                    ->visible(
                        fn (): bool => static::permits(
                            'archive expense categories'
                        )
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                    ->label('Activate Selected')
                    ->visible(
                        fn (): bool => static::permits(
                            'edit expense categories'
                        )
                    )->icon('heroicon-o-play-circle')->color('success')
                    ->action(fn (Collection $records) => $records->each(fn (ExpenseCategory $r) => $r->update(['is_active' => true])))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Deactivate Selected')
                    ->visible(
                        fn (): bool => static::permits(
                            'edit expense categories'
                        )
                    )->icon('heroicon-o-pause-circle')->color('warning')
                    ->action(fn (Collection $records) => $records->each(fn (ExpenseCategory $r) => $r->update(['is_active' => false])))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('deleteUnused')
                    ->label('Delete Selected Unused')
                    ->visible(
                        fn (): bool => static::permits(
                            'delete expense categories'
                        )
                    )->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $deleted = 0; $skipped = 0;
                        foreach ($records as $record) {
                            if (! $record->canBeDeletedSafely()) { $skipped++; continue; }
                            $record->delete(); $deleted++;
                        }
                        Notification::make()->title("{$deleted} category(s) archived")
                            ->body("{$skipped} category(s) had expense history and were skipped.")
                            ->color($skipped ? 'warning' : 'success')->send();
                    })->deselectRecordsAfterCompletion(),
                Tables\Actions\RestoreBulkAction::make()
                    ->visible(
                        fn (): bool => static::permits(
                            'archive expense categories'
                        )
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListExpenseCategories::route('/')];
    }
}
