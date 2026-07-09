<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropHarvestRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'harvest_no',
        'crop_season_id',
        'harvest_date',
        'quantity',
        'unit',
        'grade_a_quantity',
        'grade_b_quantity',
        'rejected_quantity',
        'unit_value',
        'estimated_value',
        'harvested_by',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'harvest_date' => 'date',
        'quantity' => 'decimal:3',
        'grade_a_quantity' => 'decimal:3',
        'grade_b_quantity' => 'decimal:3',
        'rejected_quantity' => 'decimal:3',
        'unit_value' => 'decimal:2',
        'estimated_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CropHarvestRecord $record): void {
            if (blank($record->harvest_no)) {
                $record->harvest_no =
                    'HAR'
                    . now('Africa/Nairobi')->format('Ymd')
                    . str_pad((string) ((int) static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            $record->harvest_date ??= now('Africa/Nairobi')->toDateString();

            if (auth()->check() && blank($record->created_by)) {
                $record->created_by = auth()->id();
            }
        });

        static::saving(function (CropHarvestRecord $record): void {
            $record->estimated_value = (float) $record->quantity * (float) $record->unit_value;
        });

        static::saved(function (CropHarvestRecord $record): void {
            $record->cropSeason?->syncCropTotals();
        });

        static::deleted(function (CropHarvestRecord $record): void {
            $record->cropSeason?->syncCropTotals();
        });
    }

    public function cropSeason()
    {
        return $this->belongsTo(CropSeason::class);
    }
}
