<?php

namespace App\Console\Commands;

use App\Models\DataBackup;
use App\Models\DataBackupSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process as SymfonyProcess;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'data:backup-database
        {--manual : Mark backup as manually triggered}
        {--keep= : Number of completed backups to keep}
        {--include-routines : Also dump stored procedures/functions after MariaDB upgrade is fixed}';

    protected $description = 'Create a full database SQL backup using mysqldump.';

    public function handle(): int
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (! $connection || ($connection['driver'] ?? null) !== 'mysql') {
            $this->error('Only MySQL/MariaDB database backup is supported.');

            return self::FAILURE;
        }

        $database = $connection['database'] ?? null;
        $username = $connection['username'] ?? null;
        $password = $connection['password'] ?? null;
        $host = $connection['host'] ?? '127.0.0.1';
        $port = $connection['port'] ?? 3306;

        if (! $database || ! $username) {
            $this->error('Database name or username is missing.');

            return self::FAILURE;
        }

        /*
         * Laravel 12 local disk root is usually storage/app/private.
         * Therefore this path MUST NOT start with "private/".
         */
        $timestamp = now('Africa/Nairobi')->format('Ymd_His');
        $filename = "{$database}_backup_{$timestamp}.sql";
        $storagePath = 'data/backups/database/' . $filename;
        $absolutePath = Storage::disk('local')->path($storagePath);

        File::ensureDirectoryExists(dirname($absolutePath));

        $backup = DataBackup::query()->create([
            'status' => 'running',
            'connection' => $connectionName,
            'database_name' => $database,
            'disk' => 'local',
            'path' => $storagePath,
            'filename' => $filename,
            'triggered_by' => $this->option('manual') ? 'manual' : 'scheduled',
            'triggered_by_user_id' => auth()->id(),
            'started_at' => now(),
        ]);

        $startedAt = microtime(true);

        $mysqldumpBinary = env('MYSQLDUMP_BINARY');

        if (! $mysqldumpBinary || ! is_file($mysqldumpBinary)) {
            $mysqldumpBinary = is_file('/opt/lampp/bin/mysqldump')
                ? '/opt/lampp/bin/mysqldump'
                : 'mysqldump';
        }

        $command = [
            $mysqldumpBinary,
            '--protocol=TCP',
            "--host={$host}",
            "--port={$port}",
            "--user={$username}",
            '--single-transaction',
            '--quick',
            '--triggers',
            '--skip-events',
            '--default-character-set=utf8mb4',
            $database,
        ];

        if ($this->option('include-routines')) {
            $command[] = '--routines';
        }

        $process = new SymfonyProcess($command);
        $process->setTimeout(null);

        if (filled($password)) {
            $process->setEnv([
                'MYSQL_PWD' => $password,
            ]);
        }

        $errorOutput = '';
        $fileHandle = fopen($absolutePath, 'wb');

        if (! $fileHandle) {
            $backup->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => (int) round(microtime(true) - $startedAt),
                'error_message' => 'Could not open backup file for writing.',
            ]);

            return self::FAILURE;
        }

        try {
            $process->run(function (string $type, string $buffer) use ($fileHandle, &$errorOutput): void {
                if ($type === SymfonyProcess::OUT) {
                    fwrite($fileHandle, $buffer);

                    return;
                }

                $errorOutput .= $buffer;
            });
        } finally {
            fclose($fileHandle);
        }

        if (! $process->isSuccessful()) {
            @unlink($absolutePath);

            $backup->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => (int) round(microtime(true) - $startedAt),
                'error_message' => trim($errorOutput ?: $process->getErrorOutput()),
            ]);

            $this->error('Database backup failed.');
            $this->line(trim($errorOutput ?: $process->getErrorOutput()));

            return self::FAILURE;
        }

        $size = is_file($absolutePath) ? filesize($absolutePath) : null;

        if (! $size || $size < 1) {
            @unlink($absolutePath);

            $backup->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => (int) round(microtime(true) - $startedAt),
                'error_message' => 'Backup file was created but it is empty.',
            ]);

            return self::FAILURE;
        }

        $backup->update([
            'status' => 'completed',
            'size_bytes' => $size,
            'finished_at' => now(),
            'duration_seconds' => (int) round(microtime(true) - $startedAt),
            'error_message' => filled($errorOutput) ? trim($errorOutput) : null,
        ]);

        $this->info("Database backup completed: {$filename}");

        $this->cleanupOldBackups();

        return self::SUCCESS;
    }

    private function cleanupOldBackups(): void
    {
        $keep = (int) ($this->option('keep') ?: DataBackupSetting::current()->keep_last ?: 14);

        if ($keep < 1) {
            $keep = 14;
        }

        DataBackup::query()
            ->where('status', 'completed')
            ->whereNull('archived_at')
            ->latest('finished_at')
            ->skip($keep)
            ->take(500)
            ->get()
            ->each(function (DataBackup $backup): void {
                if ($backup->path) {
                    Storage::disk('local')->delete($backup->path);
                }

                $backup->delete();
            });
    }
}
