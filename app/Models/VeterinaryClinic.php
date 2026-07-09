<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VeterinaryClinic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'contact_person',
        'phone',
        'email',
        'address',
        'county',
        'sub_county',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $clinic): void {
            if (auth()->check()) {
                $clinic->created_by ??= auth()->id();
                $clinic->updated_by ??= auth()->id();
            }
        });

        static::updating(function (self $clinic): void {
            if (auth()->check()) {
                $clinic->updated_by = auth()->id();
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(
            AnimalLabRequest::class,
            'veterinary_clinic_id'
        );
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
        $label = $this->name;

        if (filled($this->type)) {
            $label .= ' · ' . $this->type;
        }

        if (filled($this->county)) {
            $label .= ' — ' . $this->county;
        }

        return $label;
    }
}
