<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalGroupMember extends Model
{
    protected $fillable = [
        'animal_group_id',
        'animal_id',
        'joined_at',
        'left_at',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AnimalGroup::class, 'animal_group_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
