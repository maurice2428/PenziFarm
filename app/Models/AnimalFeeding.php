<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnimalFeeding extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'feeding_no',
        'feeding_date',
        'target_type',
        'breed_id',
        'location_id',
        'total_animals',
        'total_feed_quantity',
        'total_cost',
        'notes',
        'fed_by',
        'created_by',
    ];

    protected $casts = [
        'feeding_date' => 'date',
        'total_feed_quantity' => 'decimal:3',
        'total_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnimalFeeding $feeding): void {
            if (blank($feeding->feeding_no)) {
                $feeding->feeding_no =
                    'FEED' .
                    now('Africa/Nairobi')->format('Ymd') .
                    str_pad((string) ((int) static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            if (auth()->check()) {
                $feeding->created_by ??= auth()->id();
                $feeding->fed_by ??= auth()->id();
            }

            $feeding->feeding_date ??= now('Africa/Nairobi')->toDateString();
        });
    }

    public function items()
    {
        return $this->hasMany(AnimalFeedingItem::class);
    }

    public function animals()
    {
        return $this->belongsToMany(Animal::class, 'animal_feeding_animal');
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'referenceable');
    }

    public function breed()
    {
        return $this->belongsTo(Breed::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
