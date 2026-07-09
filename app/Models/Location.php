<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'address',
        'county',
        'sub_county',
        'ward',
        'latitude',
        'longitude',
        'place_label',
        'is_active',
        'is_default',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $location): void {
            if (! $location->is_default) {
                return;
            }

            $query = static::query();

            if ($location->exists) {
                $query->whereKeyNot($location->getKey());
            }

            $query->update(['is_default' => false]);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefaultFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_default')
            ->orderBy('name');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'current_location_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getDisplayNameAttribute(): string
    {
        return ($this->is_default ? 'Default • ' : '')
            . $this->name
            . ($this->county ? ' — ' . $this->county : '');
    }
}
