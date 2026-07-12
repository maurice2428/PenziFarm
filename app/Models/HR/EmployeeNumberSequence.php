<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;

class EmployeeNumberSequence extends Model
{
    protected $fillable = [
        'prefix',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];
}
