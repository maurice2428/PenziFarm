<?php

namespace App\Models\Projects;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectProgressUpdate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farm_project_id',
        'project_milestone_id',
        'update_date',
        'title',
        'narrative',
        'progress_percent',
        'weather_condition',
        'work_done',
        'blockers',
        'next_steps',
        'photo_path',
        'created_by',
    ];

    protected $casts = [
        'update_date' => 'date',
        'progress_percent' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(FarmProject::class, 'farm_project_id');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'project_milestone_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
