<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingTaxSettingResource\Pages;
use App\Models\Accounting\AccountingTaxSetting;
use App\Services\Accounting\AccountingBulkExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AccountingTaxSettingResource extends Resource
{
    protected static ?string $model = AccountingTaxSetting::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Kenya Tax & Compliance';
    protected static ?string $navigationLabel = 'Tax Rules';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool { return auth()->user()?->can('view accounting tax settings') ?? false; }
    public static function canAccess(): bool { return static::shouldRegisterNavigation(); }
    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Kenya Tax Rule')->icon('heroicon-o-receipt-percent')
                ->description('Rates are configurable by effective date. Verify changes against the current KRA law and your tax adviser before activation.')
                ->columns(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true)->maxLength(50),
                    Forms\Components\Select::make('type')->native(false)->required()->options([
                        'vat' => 'VAT', 'withholding' => 'Withholding Tax', 'withholding_vat' => 'Withholding VAT',
                        'corporation_tax' => 'Corporation Tax', 'turnover_tax' => 'Turnover Tax', 'paye' => 'PAYE',
                        'nssf' => 'NSSF', 'shif' => 'SHIF', 'housing_levy' => 'Housing Levy', 'other' => 'Other',
                    ]),
                    Forms\Components\Select::make('tax_scope')->native(false)->options([
                        'sales' => 'Sales / Output', 'purchases' => 'Purchases / Input', 'payments' => 'Payments',
                        'payroll' => 'Payroll', 'corporate' => 'Corporate', 'general' => 'General',
                    ])->default('general'),
                    Forms\Components\TextInput::make('rate')->numeric()->minValue(0)->suffix('%'),
                    Forms\Components\TextInput::make('resident_rate')->numeric()->minValue(0)->suffix('%'),
                    Forms\Components\TextInput::make('non_resident_rate')->numeric()->minValue(0)->suffix('%'),
                    Forms\Components\TextInput::make('fixed_amount')->numeric()->minValue(0)->prefix('KES'),
                    Forms\Components\DatePicker::make('effective_from')->native(false),
                    Forms\Components\DatePicker::make('effective_to')->native(false)->afterOrEqual('effective_from'),
                    Forms\Components\TextInput::make('return_due_day')->numeric()->minValue(1)->maxValue(31),
                    Forms\Components\TextInput::make('remittance_due_days')->numeric()->minValue(1)->maxValue(60),
                    Forms\Components\Toggle::make('requires_etims')->label('eTIMS Evidence Required'),
                    Forms\Components\Toggle::make('is_system')->label('System Rule'),
                    Forms\Components\Toggle::make('is_default')->label('Default Rule'),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\TextInput::make('legal_reference')->columnSpan(['default' => 1, 'xl' => 2]),
                    Forms\Components\Select::make('filing_frequency')->native(false)->options(['monthly'=>'Monthly','quarterly'=>'Quarterly','annual'=>'Annual','transactional'=>'Per Transaction']),
                    Forms\Components\TextInput::make('kra_return_code'),
                    Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
                ]),
        ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting tax settings') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting tax settings') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('type')->columns([
            Tables\Columns\TextColumn::make('code')->searchable()->weight('bold')->copyable(),
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('type')->badge(),
            Tables\Columns\TextColumn::make('tax_scope')->label('Scope')->badge(),
            Tables\Columns\TextColumn::make('rate')->suffix('%')->placeholder('Variable'),
            Tables\Columns\TextColumn::make('resident_rate')->label('Resident')->suffix('%')->placeholder('-'),
            Tables\Columns\TextColumn::make('non_resident_rate')->label('Non-resident')->suffix('%')->placeholder('-'),
            Tables\Columns\TextColumn::make('effective_from')->date('d M Y')->placeholder('Always'),
            Tables\Columns\TextColumn::make('effective_to')->date('d M Y')->placeholder('Open-ended'),
            Tables\Columns\IconColumn::make('requires_etims')->boolean()->label('eTIMS'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('type')->options([
                'vat' => 'VAT', 'withholding' => 'Withholding Tax', 'withholding_vat' => 'Withholding VAT', 'corporation_tax' => 'Corporation Tax', 'turnover_tax' => 'Turnover Tax', 'paye' => 'PAYE', 'other' => 'Other',
            ]),
            Tables\Filters\TernaryFilter::make('is_active'),
        ])->actions([
            Tables\Actions\EditAction::make()
                ->visible(fn (AccountingTaxSetting $record): bool => static::canEdit($record)),
        ])
        ->bulkActions([
            Tables\Actions\BulkAction::make('activate')->label('Activate Selected')->visible(fn (): bool => auth()->user()?->can('activate accounting tax settings') ?? false)->color('success')->icon('heroicon-o-play')->action(fn (Collection $records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('deactivate')->label('Deactivate Selected')->visible(fn (): bool => auth()->user()?->can('deactivate accounting tax settings') ?? false)->color('warning')->icon('heroicon-o-pause')->action(fn (Collection $records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->visible(fn (): bool => auth()->user()?->can('export accounting tax settings') ?? false)->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn (Collection $records) => app(AccountingBulkExportService::class)->csv($records, [
                'Code' => 'code', 'Name' => 'name', 'Type' => 'type', 'Scope' => 'tax_scope', 'Rate' => 'rate', 'Resident Rate' => 'resident_rate', 'Nonresident Rate' => 'non_resident_rate', 'Effective From' => fn ($r) => $r->effective_from?->format('Y-m-d'), 'Effective To' => fn ($r) => $r->effective_to?->format('Y-m-d'), 'Active' => 'is_active',
            ], 'tax-rules-' . now()->format('Ymd_His') . '.csv')),
        ]);
    }

    public static function getPages(): array { return ['index' => Pages\ListAccountingTaxSettings::route('/'), 'create' => Pages\CreateAccountingTaxSetting::route('/create'), 'edit' => Pages\EditAccountingTaxSetting::route('/{record}/edit')]; }
}
