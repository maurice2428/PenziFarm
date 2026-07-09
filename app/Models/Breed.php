<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    protected static function booted(): void
    {
        static::saving(function (Breed $breed): void {
            $breed->prefix = static::generatePrefix($breed->breed_name);

            if (! $breed->created_by && auth()->check()) {
                $breed->created_by = auth()->id();
            }
        });
    }

    public static function parentCategories(): array
    {
        return [
            'Sheep' => 'Sheep',
            'Goat' => 'Goat',
            'Cattle' => 'Cattle',
            'Poultry' => 'Poultry',
        ];
    }

    /**
     * Generate a three-character uppercase prefix from the breed name.
     *
     * Examples:
     * Dorper      => DOR
     * Boer        => BOE
     * Kalahari    => KAL
     * Red Maasai  => RED
     */
    public static function generatePrefix(?string $breedName): ?string
    {
        $cleanName = strtoupper(
            (string) preg_replace(
                '/[^A-Za-z0-9]/',
                '',
                trim((string) $breedName)
            )
        );

        if ($cleanName === '') {
            return null;
        }

        return substr($cleanName, 0, 3);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function breedCounter(): HasOne
    {
        return $this->hasOne(BreedCounter::class);
    }
}
