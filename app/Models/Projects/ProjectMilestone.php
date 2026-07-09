<?php

namespace App\Models\Projects;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectMilestone extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farm_project_id',
        'title',
        'description',
        'status',
        'progress_percent',
        'target_date',
        'completed_at',
        'budget_amount',
        'spent_amount',
        'created_by',
    ];

    protected $casts = [
        'target_date' => 'date',
        'completed_at' => 'date',
        'progress_percent' => 'integer',
        'budget_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(FarmProject::class, 'farm_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
