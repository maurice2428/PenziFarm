<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetValuation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farm_asset_id',
        'valuation_date',
        'valuation_type',
        'previous_value',
        'valuation_amount',
        'depreciation_amount',
        'condition',
        'valuer_name',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'previous_value' => 'decimal:2',
        'valuation_amount' => 'decimal:2',
        'depreciation_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (AssetValuation $valuation): void {
            if (auth()->check() && blank($valuation->created_by)) {
                $valuation->created_by = auth()->id();
            }

            if (blank($valuation->valuation_date)) {
                $valuation->valuation_date = now('Africa/Nairobi')->toDateString();
            }
        });

        static::saved(function (AssetValuation $valuation): void {
            $asset = $valuation->farmAsset;

            if (!$asset) {
                return;
            }

            $asset->forceFill([
                'current_value' => $valuation->valuation_amount,
                'last_valuation_date' => $valuation->valuation_date,
                'condition' => $valuation->condition ?: $asset->condition,
            ])->saveQuietly();
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

    public function getValuationTypeLabelAttribute(): string
    {
        return str($this->valuation_type ?: 'revaluation')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
