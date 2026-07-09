<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DataDirectory extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'path',
        'description',
        'created_by_user_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (DataDirectory $directory): void {
            $directory->slug = Str::slug($directory->name);

            $parentPath = null;

            if ($directory->parent_id) {
                $parentPath = static::query()
                    ->whereKey($directory->parent_id)
                    ->value('path');
            }

            $directory->path = trim(
                ($parentPath ? $parentPath . '/' : '') . $directory->slug,
                '/'
            );
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DataDirectory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DataDirectory::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DataDocument::class, 'directory_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
