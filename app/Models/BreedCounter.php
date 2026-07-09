<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreedCounter extends Model
{
    protected $fillable = [
        'breed_id',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }
}
