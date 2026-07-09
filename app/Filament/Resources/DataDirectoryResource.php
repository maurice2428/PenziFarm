<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataDirectoryResource\Pages;
use App\Models\DataDirectory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DataDirectoryResource extends Resource
{
    protected static ?string $model = DataDirectory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Data Center';

    protected static ?string $navigationLabel = 'Directories';

    protected static ?int $navigationSort = 2;
     public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view data directories') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create data directories') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('edit data directories') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete data directories') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete data directories') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Directory Details')
                    ->description('Create logical folders for farm documents, pictures, reports, contracts and backup references.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->label('Parent Directory')
                            ->options(function (?DataDirectory $record): array {
                                return DataDirectory::query()
                                    ->when(
                                        $record?->exists,
                                        fn ($query) => $query
                                            ->whereKeyNot($record->getKey())
                                            ->where('path', 'not like', $record->path . '/%')
                                    )
                                    ->orderBy('path')
                                    ->pluck('path', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leave empty to create a main/root directory.'),

                        Forms\Components\TextInput::make('name')
                            ->label('Directory Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Example: Animal Health Reports'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Optional note about what this directory stores.'),

                        Forms\Components\Hidden::make('created_by_user_id')
                            ->default(fn () => auth()->id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Data Directories')
            ->description('Create and manage document folders used by the farm data library.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Directory')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-folder')
                    ->copyable(),

                Tables\Columns\TextColumn::make('path')
                    ->label('Path')
                    ->searchable()
                    ->copyable()
                    ->limit(55)
                    ->tooltip(fn (DataDirectory $record): ?string => $record->path)
                    ->wrap(),

                Tables\Columns\TextColumn::make('parent.path')
                    ->label('Parent')
                    ->default('Root')
                    ->limit(35)
                    ->tooltip(fn (DataDirectory $record): ?string => $record->parent?->path)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Documents')
                    ->counts('documents')
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('children_count')
                    ->label('Subfolders')
                    ->counts('children')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label('Created By')
                    ->default('System')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (DataDirectory $record): bool => static::canEdit($record)),

                Tables\Actions\Action::make('deleteDirectory')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (DataDirectory $record): bool => static::canDelete($record))
                    ->requiresConfirmation()
                    ->modalHeading('Delete directory?')
                    ->modalDescription('Only empty directories can be deleted. Move or delete documents and subfolders first.')
                    ->action(function (DataDirectory $record): void {
                        if ($record->documents()->exists() || $record->children()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Directory not empty')
                                ->body('This directory has documents or subfolders. Move or delete them first.')
                                ->send();

                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->success()
                            ->title('Directory deleted')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('deleteSelectedDirectories')
                        ->label('Delete Selected Empty Directories')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn (): bool => static::canDeleteAny())
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected directories?')
                        ->modalDescription('Only empty directories will be deleted. Directories with documents or subfolders will be skipped.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $deleted = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if ($record->documents()->exists() || $record->children()->exists()) {
                                    $skipped++;

                                    continue;
                                }

                                $record->delete();
                                $deleted++;
                            }

                            Notification::make()
                                ->success()
                                ->title('Directory cleanup completed')
                                ->body("Deleted {$deleted} directorie(s). Skipped {$skipped} non-empty directorie(s).")
                                ->send();
                        }),
                ])->label('Selected Directories'),
            ])
            ->emptyStateIcon('heroicon-o-folder-open')
            ->emptyStateHeading('No directories created yet')
            ->emptyStateDescription('Create folders for documents, pictures, reports, receipts, contracts and other farm data.')
            ->defaultSort('path');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataDirectories::route('/'),
            'create' => Pages\CreateDataDirectory::route('/create'),
            'edit' => Pages\EditDataDirectory::route('/{record}/edit'),
        ];
    }
}
