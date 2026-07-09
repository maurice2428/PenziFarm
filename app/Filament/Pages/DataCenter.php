<?php

namespace App\Filament\Pages;

use App\Models\DataBackup;
use App\Models\DataBackupSetting;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DataCenter extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Data Center';

    protected static ?string $navigationGroup = 'Data Center';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Data Center';

    protected static ?string $slug = 'data-center';

    protected static string $view = 'filament.pages.data-center';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view system settings')
            || auth()->user()?->can('view settings')
            || auth()->user()?->hasAnyRole(['Administrator', 'Admin', 'Director']);
    }

    public function getViewData(): array
    {
        $completedSize = (int) DataBackup::query()
            ->where('status', 'completed')
            ->sum('size_bytes');

        return [
            'backupSetting' => DataBackupSetting::current(),
            'completedBackups' => DataBackup::query()->where('status', 'completed')->count(),
            'failedBackups' => DataBackup::query()->where('status', 'failed')->count(),
            'archivedBackups' => DataBackup::query()->whereNotNull('archived_at')->count(),
            'totalBackupSize' => self::formatBytes($completedSize),
            'latestBackup' => DataBackup::query()->latest('started_at')->first(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DataBackup::query()
                    ->latest('started_at')
            )
            ->heading('Database Backup History')
            ->description('Select one or more backup records to download, archive, restore, or delete.')
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->label('Backup File')
                    ->searchable()
                    ->weight('bold')
                    ->copyable()
                    ->limit(42)
                    ->tooltip(fn (DataBackup $record): ?string => $record->filename)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'running' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('triggered_by')
                    ->label('Trigger')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'manual' ? 'primary' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('triggeredByUser.name')
                    ->label('User')
                    ->default('System')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn ($state): string => self::formatBytes($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Finished')
                    ->dateTime('d M Y, H:i:s')
                    ->placeholder('Still running')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state): string => $state ? $state . ' sec' : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('archived_at')
                    ->label('Archived')
                    ->dateTime('d M Y, H:i')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Active')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'running' => 'Running',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('archive_state')
                    ->label('Archive State')
                    ->options([
                        'active' => 'Active only',
                        'archived' => 'Archived only',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active' => $query->whereNull('archived_at'),
                            'archived' => $query->whereNotNull('archived_at'),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn (DataBackup $record): bool => $record->status === 'completed')
                    ->action(fn (DataBackup $record) => $this->downloadOneBackup($record)),

                Tables\Actions\Action::make('archive')
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn (DataBackup $record): bool => blank($record->archived_at))
                    ->requiresConfirmation()
                    ->action(function (DataBackup $record): void {
                        $record->update([
                            'archived_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Backup archived')
                            ->send();
                    }),

                Tables\Actions\Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (DataBackup $record): bool => filled($record->archived_at))
                    ->requiresConfirmation()
                    ->action(function (DataBackup $record): void {
                        $record->update([
                            'archived_at' => null,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Backup restored')
                            ->send();
                    }),

                Tables\Actions\Action::make('viewError')
                    ->label('Error')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (DataBackup $record): bool => filled($record->error_message))
                    ->modalHeading('Backup Error')
                    ->modalContent(fn (DataBackup $record) => view('filament.pages.partials.backup-error', [
                        'error' => $record->error_message,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\Action::make('deleteBackup')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete backup record and file?')
                    ->modalDescription('This will remove the backup file from storage and delete the database record.')
                    ->action(function (DataBackup $record): void {
                        $this->deleteBackupRecord($record);

                        Notification::make()
                            ->success()
                            ->title('Backup deleted')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('downloadSelectedZip')
                        ->label('Download Selected as ZIP')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $this->downloadBackupsAsZip(
                            $records,
                            'selected-database-backups'
                        )),

                    Tables\Actions\BulkAction::make('archiveSelected')
                        ->label('Archive Selected')
                        ->icon('heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $count = 0;

                            foreach ($records as $record) {
                                if (blank($record->archived_at)) {
                                    $record->update([
                                        'archived_at' => now(),
                                    ]);

                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Selected backups archived')
                                ->body("{$count} backup record(s) archived.")
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('restoreSelected')
                        ->label('Restore Selected')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $count = 0;

                            foreach ($records as $record) {
                                if (filled($record->archived_at)) {
                                    $record->update([
                                        'archived_at' => null,
                                    ]);

                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Selected backups restored')
                                ->body("{$count} backup record(s) restored.")
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deleteSelected')
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected backups?')
                        ->modalDescription('This will delete the selected backup files and their database records.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $count = 0;

                            foreach ($records as $record) {
                                $this->deleteBackupRecord($record);
                                $count++;
                            }

                            Notification::make()
                                ->success()
                                ->title('Selected backups deleted')
                                ->body("{$count} backup record(s) deleted.")
                                ->send();
                        }),
                ])->label('Selected Backups'),
            ])
            ->defaultPaginationPageOption(10);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('runBackupNow')
                ->label('Backup Database Now')
                ->icon('heroicon-o-circle-stack')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Run full database backup now?')
                ->modalDescription('This will create a full SQL backup of the current database.')
                ->action(function (): void {
                    Artisan::call('data:backup-database', [
                        '--manual' => true,
                    ]);

                    $output = trim(Artisan::output());

                    $latest = DataBackup::query()->latest('started_at')->first();

                    if ($latest?->status === 'completed') {
                        Notification::make()
                            ->success()
                            ->title('Database backup completed')
                            ->body($latest->filename)
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Database backup failed')
                            ->body($output ?: 'Open the backup error record for details.')
                            ->send();
                    }

                    $this->resetTable();
                }),

            Actions\Action::make('downloadAllBackups')
                ->label('Download All Backups')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    $records = DataBackup::query()
                        ->where('status', 'completed')
                        ->latest('started_at')
                        ->get();

                    return $this->downloadBackupsAsZip(
                        $records,
                        'all-database-backups'
                    );
                }),

            Actions\Action::make('backupSchedule')
                ->label('Backup Schedule')
                ->icon('heroicon-o-clock')
                ->color('primary')
                ->mountUsing(function ($form): void {
                    $setting = DataBackupSetting::current();

                    $form->fill([
                        'is_enabled' => $setting->is_enabled,
                        'run_time' => substr((string) $setting->run_time, 0, 5),
                        'timezone' => $setting->timezone,
                        'keep_last' => $setting->keep_last,
                    ]);
                })
                ->form([
                    Forms\Components\Toggle::make('is_enabled')
                        ->label('Enable Scheduled Database Backup')
                        ->default(true),

                    Forms\Components\TimePicker::make('run_time')
                        ->label('Backup Time')
                        ->seconds(false)
                        ->required(),

                    Forms\Components\Select::make('timezone')
                        ->label('Timezone')
                        ->options([
                            'Africa/Nairobi' => 'Africa/Nairobi',
                            'UTC' => 'UTC',
                        ])
                        ->default('Africa/Nairobi')
                        ->required(),

                    Forms\Components\TextInput::make('keep_last')
                        ->label('Keep Latest Backups')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->default(14)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    DataBackupSetting::query()->updateOrCreate(
                        ['id' => 1],
                        [
                            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
                            'run_time' => $data['run_time'],
                            'timezone' => $data['timezone'] ?? 'Africa/Nairobi',
                            'keep_last' => (int) ($data['keep_last'] ?? 14),
                        ]
                    );

                    Notification::make()
                        ->success()
                        ->title('Backup schedule saved')
                        ->send();
                }),

            Actions\ActionGroup::make([
                Actions\Action::make('clearApplicationCache')
                    ->label('Clear Application Cache')
                    ->icon('heroicon-o-trash')
                    ->color('gray')
                    ->action(function (): void {
                        Artisan::call('cache:clear');

                        Notification::make()
                            ->success()
                            ->title('Application cache cleared')
                            ->send();
                    }),

                Actions\Action::make('clearViews')
                    ->label('Clear Views')
                    ->icon('heroicon-o-document-minus')
                    ->color('gray')
                    ->action(function (): void {
                        Artisan::call('view:clear');

                        Notification::make()
                            ->success()
                            ->title('Compiled views cleared')
                            ->send();
                    }),

                Actions\Action::make('clearRoutes')
                    ->label('Clear Routes')
                    ->icon('heroicon-o-map')
                    ->color('gray')
                    ->action(function (): void {
                        Artisan::call('route:clear');

                        Notification::make()
                            ->success()
                            ->title('Route cache cleared')
                            ->send();
                    }),

                Actions\Action::make('clearConfig')
                    ->label('Clear Config')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('gray')
                    ->action(function (): void {
                        Artisan::call('config:clear');

                        Notification::make()
                            ->success()
                            ->title('Config cache cleared')
                            ->send();
                    }),

                Actions\Action::make('clearFilament')
                    ->label('Clear Filament Components')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('gray')
                    ->action(function (): void {
                        try {
                            Artisan::call('filament:clear-cached-components');
                        } catch (\Throwable) {
                            //
                        }

                        Notification::make()
                            ->success()
                            ->title('Filament cached components cleared')
                            ->send();
                    }),

                Actions\Action::make('optimizeClear')
                    ->label('Optimize Clear All')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        Artisan::call('optimize:clear');

                        try {
                            Artisan::call('filament:clear-cached-components');
                        } catch (\Throwable) {
                            //
                        }

                        Notification::make()
                            ->success()
                            ->title('All framework caches cleared')
                            ->send();
                    }),
            ])
                ->label('Cache Tools')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('gray'),
        ];
    }

    private function downloadOneBackup(DataBackup $record)
    {
        if (! $record->path || ! Storage::disk('local')->exists($record->path)) {
            Notification::make()
                ->danger()
                ->title('Backup file missing')
                ->body('The database record exists, but the actual backup file was not found.')
                ->send();

            return null;
        }

        return Storage::disk('local')->download(
            $record->path,
            $record->filename ?: basename($record->path)
        );
    }

    private function downloadBackupsAsZip(Collection|\Illuminate\Support\Collection $records, string $prefix)
    {
        if (! class_exists(\ZipArchive::class)) {
            Notification::make()
                ->danger()
                ->title('ZIP extension missing')
                ->body('Install PHP ZIP extension first: sudo apt install php-zip -y')
                ->send();

            return null;
        }

        $records = $records
            ->filter(fn (DataBackup $record): bool =>
                $record->status === 'completed'
                && filled($record->path)
                && Storage::disk('local')->exists($record->path)
            )
            ->values();

        if ($records->isEmpty()) {
            Notification::make()
                ->warning()
                ->title('No downloadable backups found')
                ->body('Only completed backups with existing files can be downloaded.')
                ->send();

            return null;
        }

        $zipDirectory = storage_path('app/private/data/backups/exports');
        File::ensureDirectoryExists($zipDirectory);

        $zipFilename = $prefix . '-' . now('Africa/Nairobi')->format('Ymd_His') . '.zip';
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
            $absolutePath = Storage::disk('local')->path($record->path);
            $nameInsideZip = $record->filename ?: basename($record->path);

            if (is_file($absolutePath)) {
                $zip->addFile($absolutePath, $nameInsideZip);
            }
        }

        $zip->close();

        return response()
            ->download($zipPath, $zipFilename)
            ->deleteFileAfterSend(true);
    }

    private function deleteBackupRecord(DataBackup $record): void
    {
        if ($record->path) {
            Storage::disk('local')->delete($record->path);
        }

        $record->delete();
    }

    public static function formatBytes($bytes): string
    {
        if (! $bytes) {
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
}
