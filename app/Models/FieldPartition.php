<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FieldPartition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farm_field_id',
        'partition_code',
        'name',
        'area',
        'area_unit',
        'status',
        'map_coordinates',
        'notes',
    ];

    protected $casts = [
        'area' => 'decimal:3',
        'map_coordinates' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (FieldPartition $partition): void {
            if (blank($partition->partition_code)) {
                $partition->partition_code =
                    'BLK'
                    . now('Africa/Nairobi')->format('Ymd')
                    . str_pad((string) ((int) static::withTrashed()->max('id') + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function farmField()
    {
        return $this->belongsTo(FarmField::class);
    }

    public function cropSeasons()
    {
        return $this->hasMany(CropSeason::class);
    }

    public function nurseryBatches()
    {
        return $this->hasMany(NurseryBatch::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status ?: 'vacant')->replace('_', ' ')->title()->toString();
    }
}
