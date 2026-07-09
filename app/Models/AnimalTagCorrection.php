<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalTagCorrection extends Model
{
    protected $fillable = [
        'animal_id',
        'old_tag_number',
        'new_tag_number',
        'old_breed_id',
        'new_breed_id',
        'old_date_of_birth',
        'new_date_of_birth',
        'correction_type',
        'reason',
        'corrected_by',
    ];

    protected function casts(): array
    {
        return [
            'old_date_of_birth' => 'date',
            'new_date_of_birth' => 'date',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function oldBreed(): BelongsTo
    {
        return $this->belongsTo(Breed::class, 'old_breed_id');
    }

    public function newBreed(): BelongsTo
    {
        return $this->belongsTo(Breed::class, 'new_breed_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}
