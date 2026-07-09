<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropCareTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'task_no',
        'crop_catalog_id',
        'crop_season_id',
        'nursery_batch_id',
        'due_date',
        'task_type',
        'title',
        'instructions',
        'status',
        'completed_at',
        'completed_by',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CropCareTask $task): void {
            if (blank($task->task_no)) {
                $task->task_no =
                    'CCT'
                    . now('Africa/Nairobi')->format('Ymd')
                    . str_pad((string) ((int) static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            if (auth()->check() && blank($task->created_by)) {
                $task->created_by = auth()->id();
            }
        });
    }

    public function cropCatalog()
    {
        return $this->belongsTo(CropCatalog::class);
    }

    public function cropSeason()
    {
        return $this->belongsTo(CropSeason::class);
    }

    public function nurseryBatch()
    {
        return $this->belongsTo(NurseryBatch::class);
    }
}
