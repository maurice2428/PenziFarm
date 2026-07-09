<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AnimalEvent extends Model
{
    protected $fillable = [
        'animal_id',
        'type',
        'event_date',
        'performed_by',
        'location_id',
        'breeding_batch_id',
        'breeding_record_id',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'metadata' => 'array',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function breedingBatch(): BelongsTo
    {
        return $this->belongsTo(BreedingBatch::class, 'breeding_batch_id');
    }

    public function breedingRecord(): BelongsTo
    {
        return $this->belongsTo(BreedingRecord::class, 'breeding_record_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return str($this->type)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
