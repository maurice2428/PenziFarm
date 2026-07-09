<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidgetPreference extends Model
{
    protected $fillable = [
        'user_id',
        'dashboard_key',
        'widget_key',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
}
