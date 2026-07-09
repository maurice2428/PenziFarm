<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataBackup extends Model
{
    protected $fillable = [
        'status',
        'connection',
        'database_name',
        'disk',
        'path',
        'filename',
        'size_bytes',
        'triggered_by',
        'triggered_by_user_id',
        'started_at',
        'finished_at',
        'duration_seconds',
        'error_message',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'duration_seconds' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
