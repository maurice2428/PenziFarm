<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DataBackupSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'keep_last' => 'integer',
        ];
    }

    public static function current(): self
    {
        /*
         * Important:
         * During php artisan migrate, routes/console.php may be loaded before
         * this table exists. This fallback prevents migration crashes.
         */
        if (! Schema::hasTable('data_backup_settings')) {
            return new self([
                'is_enabled' => true,
                'run_time' => '23:00:00',
                'timezone' => 'Africa/Nairobi',
                'keep_last' => 14,
            ]);
        }

        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'is_enabled' => true,
                'run_time' => '23:00:00',
                'timezone' => 'Africa/Nairobi',
                'keep_last' => 14,
            ]
        );
    }
}
