<?php

namespace App\Models\Projects;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farm_project_id',
        'title',
        'document_type',
        'file_path',
        'description',
        'uploaded_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(FarmProject::class, 'farm_project_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
