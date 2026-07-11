<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingTaxSetting extends Model
{
    use HasFactory;

    protected $table = 'accounting_tax_settings';
    protected $guarded = [];

    protected $casts = [
        'rate' => 'decimal:4',
        'resident_rate' => 'decimal:4',
        'non_resident_rate' => 'decimal:4',
        'fixed_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'is_default' => 'boolean',
        'requires_etims' => 'boolean',
        'return_due_day' => 'integer',
        'remittance_due_days' => 'integer',
        'metadata' => 'array',
    ];

    public function scopeEffectiveOn(Builder $query, mixed $date): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            });
    }

    public function rateFor(string $residency = 'resident'): float
    {
        if ($residency === 'non_resident' && $this->non_resident_rate !== null) {
            return (float) $this->non_resident_rate;
        }

        if ($residency === 'resident' && $this->resident_rate !== null) {
            return (float) $this->resident_rate;
        }

        return (float) ($this->rate ?? 0);
    }
}
