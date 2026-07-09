<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnimalTransferItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'animal_transfer_id',
        'animal_id',
        'from_location_id',
        'to_location_id',
        'tag_number',
        'breed_name',
        'sex',
        'status',
        'received_at',
        'remarks',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(AnimalTransfer::class, 'animal_transfer_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }
}
