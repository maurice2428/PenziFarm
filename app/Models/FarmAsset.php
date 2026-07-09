<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_number',
        'name',
        'category',
        'asset_type',
        'tag_number',
        'serial_number',
        'location_id',
        'supplier_id',
        'acquisition_date',
        'purchase_cost',
        'current_value',
        'salvage_value',
        'useful_life_months',
        'depreciation_method',
        'last_valuation_date',
        'next_valuation_date',
        'condition',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'last_valuation_date' => 'date',
        'next_valuation_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'useful_life_months' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (FarmAsset $asset): void {
            if (blank($asset->asset_number)) {
                $asset->asset_number =
                    'AST'
                    . now('Africa/Nairobi')->format('Ymd')
                    . str_pad((string) ((int) static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            if (blank($asset->current_value)) {
                $asset->current_value = (float) ($asset->purchase_cost ?? 0);
            }

            if (auth()->check() && blank($asset->created_by)) {
                $asset->created_by = auth()->id();
            }
        });

        static::saving(function (FarmAsset $asset): void {
            if (blank($asset->current_value)) {
                $asset->current_value = (float) ($asset->purchase_cost ?? 0);
            }

            if (blank($asset->condition)) {
                $asset->condition = 'good';
            }

            if (blank($asset->status)) {
                $asset->status = 'active';
            }

            if (blank($asset->depreciation_method)) {
                $asset->depreciation_method = 'straight_line';
            }

            if (blank($asset->useful_life_months) || (int) $asset->useful_life_months <= 0) {
                $asset->useful_life_months = 60;
            }
        });
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function valuations()
    {
        return $this->hasMany(AssetValuation::class);
    }

    public function latestValuation()
    {
        return $this->hasOne(AssetValuation::class)->latestOfMany('valuation_date');
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(AssetMaintenanceRecord::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return str($this->category ?: 'general')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status ?: 'active')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getConditionLabelAttribute(): string
    {
        return str($this->condition ?: 'good')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getDepreciationMethodLabelAttribute(): string
    {
        return str($this->depreciation_method ?: 'straight_line')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getAgeMonthsAttribute(): int
    {
        if (!$this->acquisition_date) {
            return 0;
        }

        return max(0, $this->acquisition_date->diffInMonths(now('Africa/Nairobi')));
    }

    public function getAgeDisplayAttribute(): string
    {
        if (!$this->acquisition_date) {
            return 'N/A';
        }

        $months = $this->age_months;
        $years = intdiv($months, 12);
        $remainingMonths = $months % 12;

        if ($years <= 0) {
            return $months . ' month(s)';
        }

        return $years . ' yr(s), ' . $remainingMonths . ' mo(s)';
    }

    public function getRemainingUsefulLifeMonthsAttribute(): int
    {
        return max(0, (int) $this->useful_life_months - $this->age_months);
    }

    public function getAgingStatusAttribute(): string
    {
        if ($this->status === 'disposed') {
            return 'Disposed';
        }

        if ($this->remaining_useful_life_months <= 0) {
            return 'Fully Aged';
        }

        if ($this->remaining_useful_life_months <= 6) {
            return 'Near End of Life';
        }

        if ($this->remaining_useful_life_months <= 12) {
            return 'Aging Soon';
        }

        return 'Healthy Life';
    }

    public function getMonthlyDepreciationAttribute(): float
    {
        if ($this->depreciation_method === 'manual_valuation') {
            return 0;
        }

        $depreciableAmount = max(0, (float) $this->purchase_cost - (float) $this->salvage_value);
        $life = max(1, (int) $this->useful_life_months);

        return round($depreciableAmount / $life, 2);
    }

    public function getDepreciationToDateAttribute(): float
    {
        if ($this->depreciation_method === 'manual_valuation') {
            return round(max(0, (float) $this->purchase_cost - (float) $this->current_value), 2);
        }

        return round(min(
            max(0, (float) $this->purchase_cost - (float) $this->salvage_value),
            $this->monthly_depreciation * $this->age_months
        ), 2);
    }

    public function getEstimatedBookValueAttribute(): float
    {
        if ($this->depreciation_method === 'manual_valuation') {
            return round((float) $this->current_value, 2);
        }

        return round(max(
            (float) $this->salvage_value,
            (float) $this->purchase_cost - $this->depreciation_to_date
        ), 2);
    }

    public function getValueVarianceAttribute(): float
    {
        return round((float) $this->current_value - $this->estimated_book_value, 2);
    }

    public function getNextValuationStatusAttribute(): string
    {
        if (!$this->next_valuation_date) {
            return 'Not Scheduled';
        }

        if ($this->next_valuation_date->isPast()) {
            return 'Overdue';
        }

        if ($this->next_valuation_date->diffInDays(now('Africa/Nairobi')) <= 30) {
            return 'Due Soon';
        }

        return 'Scheduled';
    }

    public function getLocationDisplayAttribute(): string
    {
        if (!$this->location) {
            return 'N/A';
        }

        return $this->location->name
            ?? $this->location->location_name
            ?? ('Location #' . $this->location->id);
    }

    public function getSupplierDisplayAttribute(): string
    {
        if (!$this->supplier) {
            return 'N/A';
        }

        return $this->supplier->company_name
            ?? $this->supplier->name
            ?? ('Supplier #' . $this->supplier->id);
    }
}
