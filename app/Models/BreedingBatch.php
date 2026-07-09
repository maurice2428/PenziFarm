<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BreedingBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'batch_number',
        'name',
        'breeding_type',
        'male_animal_id',
        'male_breed_id',
        'species',
        'allow_cross_breeding',
        'mating_date',
        'expected_due_from',
        'expected_due_to',
        'total_females',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'allow_cross_breeding' => 'boolean',
        'mating_date' => 'date',
        'expected_due_from' => 'date',
        'expected_due_to' => 'date',
        'total_females' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (BreedingBatch $batch): void {
            if (blank($batch->batch_number)) {
                $batch->batch_number =
                    'BRD' .
                    now('Africa/Nairobi')->format('Ymd') .
                    str_pad((string) (static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            if (auth()->check() && blank($batch->created_by)) {
                $batch->created_by = auth()->id();
            }
        });
    }

    public function male()
    {
        return $this->belongsTo(Animal::class, 'male_animal_id');
    }

    public function maleBreed()
    {
        return $this->belongsTo(Breed::class, 'male_breed_id');
    }

    public function records()
    {
        return $this->hasMany(BreedingRecord::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getBreedingTypeLabelAttribute(): string
    {
        return str($this->breeding_type)->replace('_', ' ')->title()->toString();
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }
}
