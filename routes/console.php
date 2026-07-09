<?php

use App\Models\DataBackupSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('audit:sessions-close-expired')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('audit:sessions-close-expired')->everyFiveMinutes();



Schedule::call(function (): void {
    /*
     * Do not query backup settings before migrations have created the table.
     */
    if (! Schema::hasTable('data_backup_settings')) {
        return;
    }

    $setting = DataBackupSetting::current();

    if (! $setting->is_enabled) {
        return;
    }

    $timezone = $setting->timezone ?: 'Africa/Nairobi';

    $scheduledTime = Carbon::parse(
        (string) $setting->run_time,
        $timezone
    )->format('H:i');

    $currentTime = now($timezone)->format('H:i');

    if ($currentTime !== $scheduledTime) {
        return;
    }

    Artisan::call('data:backup-database');
})
    ->everyMinute()
    ->timezone('Africa/Nairobi')
    ->name('scheduled-database-backup')
    ->withoutOverlapping(120);
