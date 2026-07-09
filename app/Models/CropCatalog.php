<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropCatalog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'crop_code',
        'name',
        'variety',
        'category',
        'crop_type',
        'scientific_name',
        'cover_image',
        'germination_days_min',
        'germination_days_max',
        'transplant_days',
        'maturity_days_min',
        'maturity_days_max',
        'harvest_window_days',
        'spacing_between_rows_cm',
        'spacing_between_plants_cm',
        'seed_rate_per_acre',
        'seed_rate_unit',
        'expected_yield_per_acre',
        'yield_unit',
        'water_requirement',
        'soil_requirement',
        'care_routine',
        'fertilizer_routine',
        'spray_routine',
        'harvest_notes',
        'is_perennial',
        'supports_nursery',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_perennial' => 'boolean',
        'supports_nursery' => 'boolean',
        'is_active' => 'boolean',
        'germination_days_min' => 'integer',
        'germination_days_max' => 'integer',
        'transplant_days' => 'integer',
        'maturity_days_min' => 'integer',
        'maturity_days_max' => 'integer',
        'harvest_window_days' => 'integer',
        'spacing_between_rows_cm' => 'decimal:2',
        'spacing_between_plants_cm' => 'decimal:2',
        'seed_rate_per_acre' => 'decimal:3',
        'expected_yield_per_acre' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::creating(function (CropCatalog $crop): void {
            if (blank($crop->crop_code)) {
                $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $crop->name ?: 'CRP'), 0, 3));

                $crop->crop_code =
                    $prefix
                    . now('Africa/Nairobi')->format('ymd')
                    . str_pad((string) ((int) static::withTrashed()->max('id') + 1), 4, '0', STR_PAD_LEFT);
            }

            if (auth()->check() && blank($crop->created_by)) {
                $crop->created_by = auth()->id();
            }
        });
    }

    public function seasons()
    {
        return $this->hasMany(CropSeason::class);
    }

    public function nurseryBatches()
    {
        return $this->hasMany(NurseryBatch::class);
    }

    public function careTasks()
    {
        return $this->hasMany(CropCareTask::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->name . ($this->variety ? ' - ' . $this->variety : ''));
    }

    public function getCategoryLabelAttribute(): string
    {
        return str($this->category ?: 'general')->replace('_', ' ')->title()->toString();
    }

    public function getCropTypeLabelAttribute(): string
    {
        return str($this->crop_type ?: 'annual')->replace('_', ' ')->title()->toString();
    }
}
