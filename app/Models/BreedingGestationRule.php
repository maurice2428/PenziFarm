<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BreedingGestationRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'species',
        'breed_id',
        'gestation_days',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'gestation_days' => 'integer',
    ];

    public function breed()
    {
        return $this->belongsTo(Breed::class);
    }
}
