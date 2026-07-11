<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\Concerns\ChecksExplicitPermissions;
use App\Filament\Resources\HR\PayrollStatutoryRateResource\Pages;
use App\Models\HR\PayrollStatutoryRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PayrollStatutoryRateResource extends Resource
{
    use ChecksExplicitPermissions;

    protected static ?string $model = PayrollStatutoryRate::class;
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?string $navigationLabel = 'Statutory Rates';
    //protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?int $navigationSort = 13;

    public static function canViewAny(): bool
    {
        return static::permits(
            'view payroll statutory rates'
        ) || static::permits(
            'manage payroll statutory rates'
        );
    }

    public static function canCreate(): bool
    {
        return static::permits(
            'manage payroll statutory rates'
        );
    }

    public static function canEdit($record): bool
    {
        return static::permits(
            'manage payroll statutory rates'
        );
    }

    public static function canDelete($record): bool
    {
        return static::permits(
            'manage payroll statutory rates'
        );
    }

    public static function canRestore($record): bool
    {
        return static::permits(
            'manage payroll statutory rates'
        );
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Effective Statutory Rule')
                ->description('Rates are date-effective so historical payroll remains reproducible after legal changes.')
                ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                ->schema([
                    Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('name')->required()->columnSpan(['default' => 1, 'md' => 2]),
                    Forms\Components\Select::make('type')->options([
                        'paye' => 'PAYE', 'nssf' => 'NSSF', 'shif' => 'SHIF', 'housing_levy' => 'Housing Levy',
                    ])->required()->native(false),
                    Forms\Components\DatePicker::make('effective_from')->native(false)->required(),
                    Forms\Components\DatePicker::make('effective_to')->native(false),
                    Forms\Components\TextInput::make('employee_rate')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('employer_rate')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('minimum_amount')->numeric()->prefix('KES'),
                    Forms\Components\TextInput::make('maximum_amount')->numeric()->prefix('KES'),
                    Forms\Components\TextInput::make('lower_earning_limit')->numeric()->prefix('KES'),
                    Forms\Components\TextInput::make('upper_earning_limit')->numeric()->prefix('KES'),
                    Forms\Components\TextInput::make('personal_relief')->numeric()->prefix('KES'),
                    Forms\Components\TextInput::make('remittance_due_day')->numeric()->minValue(1)->maxValue(31),
                    Forms\Components\Placeholder::make('bands_notice')
                        ->label('PAYE Bands')
                        ->content('PAYE bands are seeded as structured date-effective JSON. Update them through a reviewed migration or seeder to preserve auditability.')
                        ->visible(fn (Forms\Get $get): bool => $get('type') === 'paye')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('legal_reference')->rows(3)->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('effective_from', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')->badge()->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(
                        fn (mixed $state): string =>
                            str((string) $state)
                                ->replace('_', ' ')
                                ->upper()
                                ->toString()
                    ),
                Tables\Columns\TextColumn::make('effective_from')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('effective_to')->date('d M Y')->placeholder('Open ended'),
                Tables\Columns\TextColumn::make('employee_rate')->suffix('%')->placeholder('N/A'),
                Tables\Columns\TextColumn::make('employer_rate')->suffix('%')->placeholder('N/A'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'paye' => 'PAYE', 'nssf' => 'NSSF', 'shif' => 'SHIF', 'housing_levy' => 'Housing Levy',
                ]),
                Tables\Filters\TernaryFilter::make('is_active')->boolean(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (): bool => static::permits(
                            'manage payroll statutory rates'
                        )
                    )
                    ->slideOver()
                    ->modalWidth('6xl'),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (): bool => static::permits(
                            'manage payroll statutory rates'
                        )
                    ),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                    ->label('Activate Selected')
                    ->visible(
                        fn (): bool => static::permits(
                            'manage payroll statutory rates'
                        )
                    )
                    ->color('success')
                    ->action(fn (Collection $records) => $records->each(fn (PayrollStatutoryRate $r) => $r->update(['is_active' => true])))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Deactivate Selected')
                    ->visible(
                        fn (): bool => static::permits(
                            'manage payroll statutory rates'
                        )
                    )
                    ->color('warning')
                    ->action(fn (Collection $records) => $records->each(fn (PayrollStatutoryRate $r) => $r->update(['is_active' => false])))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPayrollStatutoryRates::route('/')];
    }
}
