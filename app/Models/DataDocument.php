<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataDocument extends Model
{
    protected $fillable = [
        'directory_id',
        'title',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'description',
        'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function directory(): BelongsTo
    {
        return $this->belongsTo(DataDirectory::class, 'directory_id');
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
