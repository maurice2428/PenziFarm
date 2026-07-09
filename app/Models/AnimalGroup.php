<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnimalGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group_code',
        'name',
        'group_type',
        'status',
        'purpose',
        'description',
        'auto_sync',
        'location_id',
        'breed_id',
        'sex',
        'animal_status',
        'animal_purpose',
        'criteria',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'auto_sync' => 'boolean',
        'criteria' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnimalGroup $group): void {
            if (blank($group->group_code)) {
                $next = static::query()->count() + 1;

                $group->group_code = 'GRP-'
                    . now('Africa/Nairobi')->format('Ymd')
                    . '-'
                    . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            }

            $group->created_by ??= auth()->id();

            $group->animal_status = null;
            $group->animal_purpose = null;
        });

        static::updating(function (AnimalGroup $group): void {
            $group->updated_by = auth()->id();

            $group->animal_status = null;
            $group->animal_purpose = null;
        });
    }

    public function members(): HasMany
    {
        return $this->hasMany(AnimalGroupMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function matchingAnimalsQuery(): Builder
    {
        return Animal::query()
            ->where('status', 'Active')
            ->where('is_archived', false)
            ->when(
                $this->location_id,
                fn (Builder $query) => $query->where('current_location_id', $this->location_id)
            )
            ->when(
                $this->breed_id,
                fn (Builder $query) => $query->where('breed_id', $this->breed_id)
            )
            ->when(
                $this->sex,
                fn (Builder $query) => $query->where('sex', $this->sex)
            );
    }

    public function syncDynamicMembers(): int
    {
        $animalIds = $this->matchingAnimalsQuery()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $this->members()
            ->whereNotIn('animal_id', $animalIds)
            ->where('status', 'active')
            ->update([
                'status' => 'removed',
                'left_at' => now(),
            ]);

        foreach ($animalIds as $animalId) {
            $this->members()->updateOrCreate(
                [
                    'animal_id' => $animalId,
                ],
                [
                    'status' => 'active',
                    'joined_at' => now(),
                    'left_at' => null,
                    'created_by' => auth()->id(),
                ]
            );
        }

        return $animalIds->count();
    }
}
