<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Breed extends Model
{
    protected $fillable = [
        'parent_category',
        'breed_name',
        'prefix',
        'description',
        'avatar',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function parentCategories(): array
    {
        return [
            'Sheep' => 'Sheep',
            'Goat' => 'Goat',
            'Cattle' => 'Cattle',
            'Poultry' => 'Poultry',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function breedCounter()
    {
        return $this->hasOne(BreedCounter::class);
    }
}
