<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmField extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'field_code',
        'name',
        'location_id',
        'total_area',
        'area_unit',
        'soil_type',
        'irrigation_type',
        'status',
        'map_coordinates',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_area' => 'decimal:3',
        'map_coordinates' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (FarmField $field): void {
            if (blank($field->field_code)) {
                $field->field_code =
                    'FLD'
                    . now('Africa/Nairobi')->format('Ymd')
                    . str_pad((string) ((int) static::withTrashed()->max('id') + 1), 4, '0', STR_PAD_LEFT);
            }

            if (auth()->check() && blank($field->created_by)) {
                $field->created_by = auth()->id();
            }
        });
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function partitions()
    {
        return $this->hasMany(FieldPartition::class);
    }

    public function cropSeasons()
    {
        return $this->hasMany(CropSeason::class);
    }

    public function nurseryBatches()
    {
        return $this->hasMany(NurseryBatch::class);
    }

    public function getLocationDisplayAttribute(): string
    {
        if (!$this->location) {
            return 'N/A';
        }

        return $this->location->name
            ?? $this->location->location_name
            ?? ('Location #' . $this->location->id);
    }

    public function getAllocatedAreaAttribute(): float
    {
        return (float) $this->partitions()->sum('area');
    }

    public function getAvailableAreaAttribute(): float
    {
        return max(0, (float) $this->total_area - $this->allocated_area);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status ?: 'active')->replace('_', ' ')->title()->toString();
    }
}
