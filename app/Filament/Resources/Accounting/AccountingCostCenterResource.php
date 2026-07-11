<?php

namespace App\Filament\Resources\Accounting;

use App\Filament\Resources\Accounting\AccountingCostCenterResource\Pages;
use App\Models\Accounting\AccountingCostCenter;
use App\Services\Accounting\AccountingBulkExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingCostCenterResource extends Resource
{
    protected static ?string $model = AccountingCostCenter::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Accounting Setup';
    protected static ?string $navigationLabel = 'Cost Centres';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool { return auth()->user()?->can('view accounting cost centers') ?? false; }
    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }
    public static function getEloquentQuery(): Builder { return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Cost Centre')->icon('heroicon-o-building-office-2')
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])->schema([
                    Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true)->maxLength(30),
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\Select::make('type')->native(false)->required()->options([
                        'department' => 'Department', 'project' => 'Project', 'crop' => 'Crop', 'livestock' => 'Livestock',
                        'asset' => 'Asset', 'admin' => 'Administration', 'other' => 'Other',
                    ])->default('department'),
                    Forms\Components\Select::make('parent_id')->relationship('parent', 'name')->searchable()->preload()->native(false),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create accounting cost centers') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit accounting cost centers') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete accounting cost centers') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('code')
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('parent.name')->placeholder('Top level'),
                Tables\Columns\TextColumn::make('journal_lines_count')->counts('journalLines')->label('Lines')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('deleted_at')->dateTime()->label('Archived')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([Tables\Filters\SelectFilter::make('type')->options([
                'department' => 'Department', 'project' => 'Project', 'crop' => 'Crop', 'livestock' => 'Livestock', 'asset' => 'Asset', 'admin' => 'Administration', 'other' => 'Other',
            ]), Tables\Filters\TrashedFilter::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->visible(fn (AccountingCostCenter $r): bool => ! $r->journalLines()->exists()),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')->label('Activate Selected')->icon('heroicon-o-play')->color('success')->action(function (Collection $records): void { foreach ($records as $r) { if ($r->trashed()) { $r->restore(); } $r->update(['is_active' => true]); } })->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('deactivate')->label('Deactivate Selected')->icon('heroicon-o-pause')->color('warning')->action(fn (Collection $records) => $records->each(fn ($r) => $r->update(['is_active' => false])))->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('archiveEligible')->label('Archive Eligible')->icon('heroicon-o-archive-box')->color('danger')->requiresConfirmation()->action(function (Collection $records): void {
                    $done = 0; $skipped = 0;
                    foreach ($records as $r) { if ($r->journalLines()->exists()) { $skipped++; continue; } $r->delete(); $done++; }
                    Notification::make()->title("{$done} archived; {$skipped} skipped due to activity")->color($skipped ? 'warning' : 'success')->send();
                })->deselectRecordsAfterCompletion(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\BulkAction::make('exportSelected')->label('Export Selected')->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn (Collection $records) => app(AccountingBulkExportService::class)->csv($records, [
                    'Code' => 'code', 'Name' => 'name', 'Type' => 'type', 'Parent' => fn ($r) => $r->parent?->name, 'Active' => 'is_active',
                ], 'cost-centres-' . now()->format('Ymd_His') . '.csv')),
            ]);
    }

    public static function getPages(): array { return ['index' => Pages\ListAccountingCostCenters::route('/'), 'create' => Pages\CreateAccountingCostCenter::route('/create'), 'edit' => Pages\EditAccountingCostCenter::route('/{record}/edit')]; }
}
