<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropInputApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_no',
        'crop_season_id',
        'nursery_batch_id',
        'field_partition_id',
        'inventory_item_id',
        'application_date',
        'application_type',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'target_area',
        'area_unit',
        'method',
        'applied_by',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'application_date' => 'date',
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'target_area' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::creating(function (CropInputApplication $record): void {
            if (blank($record->application_no)) {
                $record->application_no =
                    'CIA'
                    . now('Africa/Nairobi')->format('Ymd')
                    . str_pad((string) ((int) static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            $record->application_date ??= now('Africa/Nairobi')->toDateString();

            if (auth()->check() && blank($record->created_by)) {
                $record->created_by = auth()->id();
            }
        });

        static::saving(function (CropInputApplication $record): void {
            $record->total_cost = (float) $record->quantity * (float) $record->unit_cost;
        });
    }

    public function cropSeason()
    {
        return $this->belongsTo(CropSeason::class);
    }

    public function nurseryBatch()
    {
        return $this->belongsTo(NurseryBatch::class);
    }

    public function fieldPartition()
    {
        return $this->belongsTo(FieldPartition::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'referenceable');
    }

    public function getApplicationTypeLabelAttribute(): string
    {
        return str($this->application_type ?: 'other')->replace('_', ' ')->title()->toString();
    }
}
