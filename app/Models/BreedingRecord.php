<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BreedingRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'breeding_batch_id',
        'female_animal_id',
        'male_animal_id',
        'female_breed_id',
        'male_breed_id',
        'species',
        'breeding_type',
        'is_cross_breed',
        'mating_date',
        'gestation_days',
        'expected_due_date',
        'inbreeding_status',
        'relationship_notes',
        'pregnancy_status',
        'pregnancy_checked_at',
        'delivery_date',
        'offspring_count',
        'delivery_notes',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_cross_breed' => 'boolean',
        'mating_date' => 'date',
        'expected_due_date' => 'date',
        'pregnancy_checked_at' => 'date',
        'delivery_date' => 'date',
        'gestation_days' => 'integer',
        'offspring_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (BreedingRecord $record): void {
            if (auth()->check() && blank($record->created_by)) {
                $record->created_by = auth()->id();
            }

            if (blank($record->pregnancy_status)) {
                $record->pregnancy_status = 'pending';
            }

            if (blank($record->inbreeding_status)) {
                $record->inbreeding_status = 'clear';
            }
        });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BreedingBatch::class, 'breeding_batch_id');
    }

    public function female(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'female_animal_id');
    }

    public function male(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'male_animal_id');
    }

    public function femaleBreed(): BelongsTo
    {
        return $this->belongsTo(Breed::class, 'female_breed_id');
    }

    public function maleBreed(): BelongsTo
    {
        return $this->belongsTo(Breed::class, 'male_breed_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPregnancyStatusLabelAttribute(): string
    {
        return str($this->pregnancy_status ?: 'pending')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getBreedingTypeLabelAttribute(): string
    {
        return str($this->breeding_type ?: 'natural')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getInbreedingStatusLabelAttribute(): string
    {
        return str($this->inbreeding_status ?: 'clear')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
