<?php

namespace App\Models\Projects;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectBudgetLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farm_project_id',
        'cost_category',
        'item_name',
        'description',
        'quantity',
        'unit',
        'unit_cost',
        'estimated_amount',
        'approved_amount',
        'actual_amount',
        'variance_amount',
        'supplier_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'estimated_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(FarmProject::class, 'farm_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return str($this->cost_category ?: 'other')
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }
}
