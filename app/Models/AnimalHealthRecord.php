<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalHealthRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'animal_id',
        'inventory_item_id',
        'type',
        'recorded_at',
        'quantity_used',
        'dosage',
        'method',
        'administered_by',
        'next_due_date',
        'diagnosis',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'recorded_at' => 'date',
        'next_due_date' => 'date',
        'quantity_used' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnimalHealthRecord $record): void {
            if (auth()->check()) {
                $record->created_by = auth()->id();
            }
        });
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return str($this->type)->replace('_', ' ')->title();
    }
}
