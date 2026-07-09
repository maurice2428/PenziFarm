<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'holiday_date', 'type', 'description', 'is_recurring_yearly', 'applies_to_all', 'is_active'
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_recurring_yearly' => 'boolean',
        'applies_to_all' => 'boolean',
        'is_active' => 'boolean',
    ];
}
