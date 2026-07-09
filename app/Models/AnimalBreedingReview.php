<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalBreedingReview extends Model
{
    protected $fillable = [
        'animal_id',
        'recommendation',
        'source',
        'performance_score',
        'reason',
        'metrics_snapshot',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'performance_score' => 'decimal:2',
        'metrics_snapshot' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getRecommendationLabelAttribute(): string
    {
        return str($this->recommendation ?: 'monitor')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
