<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetMaintenanceRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farm_asset_id',
        'maintenance_date',
        'maintenance_type',
        'cost',
        'performed_by',
        'next_service_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'next_service_date' => 'date',
        'cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (AssetMaintenanceRecord $record): void {
            if (auth()->check() && blank($record->created_by)) {
                $record->created_by = auth()->id();
            }

            if (blank($record->maintenance_date)) {
                $record->maintenance_date = now('Africa/Nairobi')->toDateString();
            }
        });
    }

    public function farmAsset()
    {
        return $this->belongsTo(FarmAsset::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMaintenanceTypeLabelAttribute(): string
    {
        return str($this->maintenance_type ?: 'routine')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
