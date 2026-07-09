<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\IncomeCategoryResource\Pages;
use App\Models\Sales\IncomeCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class IncomeCategoryResource extends Resource
{
    protected static ?string $model = IncomeCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Income(s) Category';
    protected static ?string $modelLabel = 'Income Category';
    protected static ?string $pluralModelLabel = 'Income(s) Category';
    protected static ?int $navigationSort = 1;
     public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view income categories') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create income categories') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit income categories') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete income categories') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete income categories') ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->can('restore income categories') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('restore income categories') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can('force delete income categories') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->can('force delete income categories') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Income Category Details')
                    ->description('Classify farm income for reports, sales performance, and financial standing.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Category Name')
                            ->placeholder('Example: Animal Sales')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            })
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Income Type')
                            ->required()
                            ->options(self::incomeTypeOptions())
                            ->default('other_income')
                            ->searchable(),

                        Forms\Components\TextInput::make('code')
                            ->label('Category Code')
                            ->placeholder('Auto generated if left empty')
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Default for this type')
                            ->helperText('Only one category per income type can be marked as default.')
                            ->default(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Explain what this income category is used for.')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type_label')
                    ->label('Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(45)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Income Type')
                    ->options(self::incomeTypeOptions()),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),

                TernaryFilter::make('is_default')
                    ->label('Default Status'),

                TrashedFilter::make(),
            ])
         ->actions([
    Tables\Actions\ViewAction::make()
        ->visible(fn () => auth()->user()?->can('view income categories') ?? false),

    Tables\Actions\EditAction::make()
        ->visible(fn ($record) =>
            ! $record->trashed()
            && (auth()->user()?->can('edit income categories') ?? false)
        ),

    Tables\Actions\DeleteAction::make()
        ->visible(fn ($record) =>
            ! $record->trashed()
            && (auth()->user()?->can('delete income categories') ?? false)
        ),

    Tables\Actions\RestoreAction::make()
        ->visible(fn ($record) =>
            $record->trashed()
            && (auth()->user()?->can('restore income categories') ?? false)
        ),

    Tables\Actions\ForceDeleteAction::make()
        ->visible(fn ($record) =>
            $record->trashed()
            && (auth()->user()?->can('force delete income categories') ?? false)
        ),
])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('printSelected')
                        ->label('Print Selected')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Print selected income categories')
                        ->modalDescription('This will generate a PDF report for the selected income categories.')
                        ->visible(fn () => auth()->user()?->can('export income categories') ?? false)
                        ->action(function (Collection $records) {
                            $records = $records->load(['createdBy', 'updatedBy']);

                            $generatedBy = auth()->user();
                            $generatedByRole = $generatedBy?->getRoleNames()?->first() ?? 'User';

                            $pdf = Pdf::loadView('pdfs.sales.income-categories-bulk-report', [
                                'categories' => $records,
                                'generatedBy' => $generatedBy,
                                'generatedByRole' => $generatedByRole,
                            ])->setPaper('a4', 'landscape');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'income-categories-report-' . now('Africa/Nairobi')->format('Ymd-His') . '.pdf'
                            );
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete income categories') ?? false),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('restore income categories') ?? false),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('force delete income categories') ?? false),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function incomeTypeOptions(): array
    {
        return [
            'animal_sales' => 'Animal Sales',
            'breeder_sales' => 'Breeder Sales',
            'cull_sales' => 'Cull Sales',
            'slaughter_sales' => 'Slaughter Sales',
            'milk_sales' => 'Milk Sales',
            'egg_sales' => 'Egg Sales',
            'crop_sales' => 'Crop Sales',
            'manure_sales' => 'Manure Sales',
            'service_income' => 'Service Income',
            'other_income' => 'Other Income',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomeCategories::route('/'),
            'create' => Pages\CreateIncomeCategory::route('/create'),
            'edit' => Pages\EditIncomeCategory::route('/{record}/edit'),
        ];
    }
}
