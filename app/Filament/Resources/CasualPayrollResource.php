<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CasualPayrollResource\Pages;
use App\Filament\Resources\CasualPayrollResource\RelationManagers;
use App\Filament\Resources\CasualPayrollResource\Widgets\CasualPayrollExpenseChart;
use App\Models\HR\CasualPayroll;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CasualPayrollResource extends Resource
{
    protected static ?string $model = CasualPayroll::class;

   // protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Human Resource';

    protected static ?string $navigationLabel = 'Casual(s) Payroll';

    protected static ?string $modelLabel = 'Casual(s) Payroll';

    protected static ?string $pluralModelLabel = 'Casual (s) Payroll';

    protected static ?int $navigationSort = 18;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view casual payroll') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view casual payroll') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view casual payroll') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create casual payroll') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit casual payroll') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete casual payroll') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payroll Details')
                    ->description('Record weekly casual worker payment details.')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('farm_name')
                            ->label('Farm Name')
                            ->default(fn () => setting('farm.name', 'Penzi Farm Limited'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('title')
                            ->label('Payroll Title')
                            ->default('Casual Payroll')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('work_site')
                            ->label('Work Site')
                            ->placeholder('Molo, Nakuru, Narok')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('week_start')
                            ->label('Week Start')
                            ->required(),

                        Forms\Components\DatePicker::make('week_end')
                            ->label('Week End')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Summary')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('total_casuals')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('total_days_worked')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled()
                            ->dehydrated(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Casual Payroll Register')
            ->description('Track casual labour expenses, weekly payments, and payroll reports.')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['items', 'uploader']))
            ->columns([
                Tables\Columns\TextColumn::make('farm_name')
                    ->label('Farm')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('title')
                    ->label('Payroll')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('work_site')
                    ->label('Work Site')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('week_start')
                    ->label('Week Start')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('week_end')
                    ->label('Week End')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_casuals')
                    ->label('Casuals')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_days_worked')
                    ->label('Days')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Paid')
                    ->money('KES')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\Filter::make('payroll_period')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('week_start', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('week_end', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => auth()->user()?->can('view casual payroll')),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->can('edit casual payroll')),

                Tables\Actions\Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->can('export casual payroll'))
                    ->url(fn (CasualPayroll $record) => route('casual-payroll.report', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->can('delete casual payroll')),

                Tables\Actions\RestoreAction::make()
                    ->visible(fn () => auth()->user()?->can('restore casual payroll')),

                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn () => auth()->user()?->can('force delete casual payroll')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete casual payroll')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('restore casual payroll')),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('force delete casual payroll')),
                ]),
            ])
            ->defaultSort('week_start', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CasualPayrollExpenseChart::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCasualPayrolls::route('/'),
            'create' => Pages\CreateCasualPayroll::route('/create'),
            'view' => Pages\ViewCasualPayroll::route('/{record}'),
            'edit' => Pages\EditCasualPayroll::route('/{record}/edit'),
        ];
    }
}
