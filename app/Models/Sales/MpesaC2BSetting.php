<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class MpesaC2BSetting extends Model
{
    protected $fillable = [
        'short_code',
        'environment',
        'validation_url',
        'confirmation_url',
        'response_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
