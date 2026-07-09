<?php

namespace App\Models\Projects;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmProject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_number',
        'project_category_id',
        'name',
        'project_type',
        'priority',
        'status',
        'location',
        'land_area',
        'land_area_unit',
        'description',
        'objectives',
        'scope_of_work',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'budget_amount',
        'approved_budget_amount',
        'committed_amount',
        'spent_amount',
        'balance_amount',
        'variance_amount',
        'progress_percent',
        'contractor_name',
        'contractor_phone',
        'contractor_email',
        'manager_id',
        'approved_by',
        'approved_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
        'approved_at' => 'datetime',
        'land_area' => 'decimal:2',
        'budget_amount' => 'decimal:2',
        'approved_budget_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'progress_percent' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(ProjectBudgetLine::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ProjectProgressUpdate::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status ?: 'planned')
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }

    public function getPriorityLabelAttribute(): string
    {
        return str($this->priority ?: 'medium')
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }

    public function getBudgetUsagePercentAttribute(): int
    {
        $budget = (float) ($this->approved_budget_amount ?: $this->budget_amount);

        if ($budget <= 0) {
            return 0;
        }

        return (int) min(100, round(((float) $this->spent_amount / $budget) * 100));
    }

    public function getIsOverBudgetAttribute(): bool
    {
        return (float) $this->spent_amount > (float) ($this->approved_budget_amount ?: $this->budget_amount);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'planned' => 'gray',
            'approved' => 'info',
            'in_progress' => 'warning',
            'on_hold' => 'danger',
            'completed' => 'success',
            'closed' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
