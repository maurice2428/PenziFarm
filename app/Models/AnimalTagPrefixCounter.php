<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalTagPrefixCounter extends Model
{
    protected $fillable = [
        'tag_prefix',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
        ];
    }
}
