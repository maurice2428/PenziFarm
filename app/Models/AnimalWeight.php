<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalWeight extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'animal_id',
        'weight_kg',
        'recorded_at',
        'recorded_by',
        'remarks',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function previousWeight(): ?self
    {
        return self::query()
            ->where('animal_id', $this->animal_id)
            ->where('recorded_at', '<', $this->recorded_at)
            ->latest('recorded_at')
            ->first();
    }

    public function getPreviousWeightKgAttribute(): ?float
    {
        return $this->previousWeight()?->weight_kg;
    }

    public function getWeightDifferenceAttribute(): ?float
    {
        if ($this->previous_weight_kg === null) {
            return null;
        }

        return (float) $this->weight_kg - (float) $this->previous_weight_kg;
    }

    public function getTrendAttribute(): string
    {
        if ($this->weight_difference === null) {
            return 'first';
        }

        if ($this->weight_difference > 0) {
            return 'gaining';
        }

        if ($this->weight_difference < 0) {
            return 'losing';
        }

        return 'stable';
    }
}
