<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalTagSequence extends Model
{
    protected $fillable = [
        'breed_id',
        'birth_year',
        'last_sequence',
    ];

    protected function casts(): array
    {
        return [
            'birth_year' => 'integer',
            'last_sequence' => 'integer',
        ];
    }
}
