<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataDocumentResource\Pages;
use App\Models\DataDirectory;
use App\Models\DataDocument;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DataDocumentResource extends Resource
{
    protected static ?string $model = DataDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Data Center';

    protected static ?string $navigationLabel = 'Document(s)';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view data documents') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create data documents') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('edit data documents') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete data documents') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete data documents') ?? false;
    }

    public static function canDownload(): bool
    {
        return auth()->user()?->can('download data documents') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Details')
                    ->description('Upload documents, PDFs, Excel files, receipts, contracts, reports and farm pictures.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Example: July Health Report'),
                        Forms\Components\Select::make('document_type')
                            ->label('Document Type')
                            ->options([
                                'Document' => 'Document',
                                'Picture' => 'Picture',
                                'PDF' => 'PDF',
                                'Excel' => 'Excel',
                                'Receipt' => 'Receipt',
                                'Contract' => 'Contract',
                                'Report' => 'Report',
                                'Other' => 'Other',
                            ])
                            ->default('Document')
                            ->required(),
                        Forms\Components\Select::make('directory_id')
                            ->label('Directory')
                            ->options(fn(): array => DataDirectory::query()
                                ->orderBy('path')
                                ->pluck('path', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Directory Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('parent_id')
                                    ->label('Parent Directory')
                                    ->options(fn(): array => DataDirectory::query()
                                        ->orderBy('path')
                                        ->pluck('path', 'id')
                                        ->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                return DataDirectory::query()->create([
                                    'name' => $data['name'],
                                    'parent_id' => $data['parent_id'] ?? null,
                                    'description' => $data['description'] ?? null,
                                    'created_by_user_id' => auth()->id(),
                                ])->getKey();
                            })
                            ->helperText('You can create a new directory directly from here.'),
                        Forms\Components\FileUpload::make('file_path')
                            ->label('File')
                            ->disk('public')
                            ->directory(function (Get $get): string {
                                $directory = DataDirectory::query()->find($get('directory_id'));

                                return 'data-library/' . ($directory?->path ?: 'uncategorized');
                            })
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/gif',
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/plain',
                                'text/csv',
                            ])
                            ->maxSize(20480)
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->preserveFilenames()
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('uploaded_by_user_id')
                            ->default(fn() => auth()->id()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Documents & Pictures')
            ->description('Preview, open, download, edit or delete uploaded farm documents safely.')
            ->columns([
                Tables\Columns\ViewColumn::make('preview')
                    ->label('Preview')
                    ->view('filament.tables.columns.data-document-preview')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon(fn(DataDocument $record): string => static::typeIcon($record))
                    ->description(fn(DataDocument $record): ?string => $record->description)
                    ->limit(45)
                    ->tooltip(fn(DataDocument $record): ?string => $record->title),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Picture' => 'success',
                        'PDF' => 'danger',
                        'Excel' => 'primary',
                        'Receipt' => 'warning',
                        'Contract' => 'info',
                        'Report' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('directory.path')
                    ->label('Directory')
                    ->default('Uncategorized')
                    ->searchable()
                    ->limit(32)
                    ->tooltip(fn(DataDocument $record): ?string => $record->directory?->path)
                    ->wrap(),
                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn($state): string => static::formatBytes($state))
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('uploadedByUser.name')
                    ->label('Uploaded By')
                    ->default('System')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('file_path')
                    ->label('Storage Path')
                    ->copyable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->visible(fn(DataDocument $record): bool =>
                        static::canViewAny() &&
                        filled($record->file_path) &&
                        Storage::disk('public')->exists($record->file_path))
                    ->modalHeading(false)
                    ->modalWidth(MaxWidth::Screen)
                    ->modalContent(fn(DataDocument $record) => view('filament.resources.data-document-resource.partials.document-preview-modal', [
                        'record' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('open')
                        ->label('Open')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('gray')
                        ->visible(fn(DataDocument $record): bool =>
                            static::canViewAny() &&
                            filled($record->file_path) &&
                            Storage::disk('public')->exists($record->file_path))
                        ->url(fn(DataDocument $record): string => route('data-documents.open', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn(DataDocument $record): bool =>
                            static::canDownload() &&
                            filled($record->file_path) &&
                            Storage::disk('public')->exists($record->file_path))
                        ->url(fn(DataDocument $record): string => route('data-documents.download', $record)),
                    Tables\Actions\EditAction::make()
                        ->visible(fn(DataDocument $record): bool => static::canEdit($record)),
                    Tables\Actions\Action::make('deleteDocument')
                        ->label('Delete')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn(DataDocument $record): bool => static::canDelete($record))
                        ->requiresConfirmation()
                        ->modalHeading('Delete document and file?')
                        ->modalDescription('This will delete the uploaded file from storage and remove the database record.')
                        ->action(function (DataDocument $record): void {
                            static::deleteDocumentFileAndRecord($record);

                            Notification::make()
                                ->success()
                                ->title('Document deleted')
                                ->send();
                        }),
                ])
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('downloadSelectedDocuments')
                        ->label('Download Selected as ZIP')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->visible(fn(): bool => static::canDownload())
                        ->deselectRecordsAfterCompletion()
                        ->action(fn(Collection $records) => static::downloadDocumentsAsZip($records)),
                    Tables\Actions\BulkAction::make('deleteSelectedDocuments')
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn(): bool => static::canDeleteAny())
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected documents?')
                        ->modalDescription('This will delete uploaded files from storage and remove their database records.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $deleted = 0;

                            foreach ($records as $record) {
                                static::deleteDocumentFileAndRecord($record);
                                $deleted++;
                            }

                            Notification::make()
                                ->success()
                                ->title('Selected documents deleted')
                                ->body("Deleted {$deleted} document record(s).")
                                ->send();
                        }),
                ])->label('Selected Documents'),
            ])
            ->emptyStateIcon('heroicon-o-document-plus')
            ->emptyStateHeading('No documents uploaded yet')
            ->emptyStateDescription('Upload farm documents, pictures, PDFs, Excel files, receipts, contracts or reports.')
            ->defaultSort('created_at', 'desc');
    }

    public static function typeIcon(DataDocument $record): string
    {
        $mime = $record->mime_type;
        $path = strtolower((string) $record->file_path);
        $type = strtolower((string) $record->document_type);

        return match (true) {
            str_starts_with((string) $mime, 'image/') || $type === 'picture' => 'heroicon-o-photo',
            $mime === 'application/pdf' || str_ends_with($path, '.pdf') => 'heroicon-o-document',
            str_contains((string) $mime, 'spreadsheet') || str_ends_with($path, '.xlsx') || str_ends_with($path, '.xls') || str_ends_with($path, '.csv') => 'heroicon-o-table-cells',
            str_contains((string) $mime, 'word') || str_ends_with($path, '.doc') || str_ends_with($path, '.docx') => 'heroicon-o-document-text',
            default => 'heroicon-o-document-text',
        };
    }

    private static function downloadDocumentsAsZip(Collection $records)
    {
        if (!class_exists(\ZipArchive::class)) {
            Notification::make()
                ->danger()
                ->title('ZIP extension missing')
                ->body('Install PHP ZIP first: sudo apt install php-zip -y')
                ->send();

            return null;
        }

        $records = $records
            ->filter(fn(DataDocument $record): bool =>
                filled($record->file_path) &&
                Storage::disk('public')->exists($record->file_path))
            ->values();

        if ($records->isEmpty()) {
            Notification::make()
                ->warning()
                ->title('No downloadable documents found')
                ->body('Only records with existing uploaded files can be downloaded.')
                ->send();

            return null;
        }

        $zipDirectory = storage_path('app/private/data/documents/exports');

        File::ensureDirectoryExists($zipDirectory);

        $zipFilename = 'selected-data-documents-' . now('Africa/Nairobi')->format('Ymd_His') . '.zip';
        $zipPath = $zipDirectory . DIRECTORY_SEPARATOR . $zipFilename;

        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            Notification::make()
                ->danger()
                ->title('Could not create ZIP file')
                ->send();

            return null;
        }

        foreach ($records as $record) {
            $absolutePath = Storage::disk('public')->path($record->file_path);

            if (!is_file($absolutePath)) {
                continue;
            }

            $directory = $record->directory?->path ?: 'uncategorized';
            $fileName = basename($record->file_path);
            $nameInsideZip = $directory . '/' . $record->getKey() . '-' . $fileName;

            $zip->addFile($absolutePath, $nameInsideZip);
        }

        $zip->close();

        return response()
            ->download($zipPath, $zipFilename)
            ->deleteFileAfterSend(true);
    }

    private static function deleteDocumentFileAndRecord(DataDocument $record): void
    {
        if ($record->file_path) {
            Storage::disk('public')->delete($record->file_path);
        }

        $record->delete();
    }

    private static function formatBytes($bytes): string
    {
        if (!$bytes) {
            return '—';
        }

        $bytes = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return number_format($bytes, 2) . ' ' . $units[$index];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataDocuments::route('/'),
            'create' => Pages\CreateDataDocument::route('/create'),
            'edit' => Pages\EditDataDocument::route('/{record}/edit'),
        ];
    }
}
