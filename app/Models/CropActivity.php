<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropActivity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'activity_no',
        'crop_season_id',
        'nursery_batch_id',
        'field_partition_id',
        'activity_date',
        'activity_type',
        'growth_stage',
        'performed_by',
        'status',
        'description',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (CropActivity $activity): void {
            if (blank($activity->activity_no)) {
                $activity->activity_no =
                    'ACT'
                    . now('Africa/Nairobi')->format('Ymd')
                    . str_pad((string) ((int) static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            $activity->activity_date ??= now('Africa/Nairobi')->toDateString();

            if (auth()->check() && blank($activity->created_by)) {
                $activity->created_by = auth()->id();
            }
        });
    }

    public function cropSeason()
    {
        return $this->belongsTo(CropSeason::class);
    }

    public function nurseryBatch()
    {
        return $this->belongsTo(NurseryBatch::class);
    }

    public function fieldPartition()
    {
        return $this->belongsTo(FieldPartition::class);
    }
}
