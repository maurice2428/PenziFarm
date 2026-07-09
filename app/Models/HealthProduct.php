<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'inventory_item_id',
        'name',
        'type',
        'species',
        'dosage_per_animal',
        'dosage_unit',
        'administration_method',
        'frequency',
        'frequency_days',
        'batch_number',
        'status',
        'description',
        'precautions',
        'reference_document',
        'created_by',
    ];

    protected $casts = [
        'dosage_per_animal' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (HealthProduct $product): void {
            if (auth()->check()) {
                $product->created_by = auth()->id();
            }
        });

        static::saved(function (HealthProduct $product): void {
            if ($product->inventory_item_id) {
                return;
            }

            $item = InventoryItem::create([
                'name' => $product->name,
                'category' => $product->type,
                'unit' => $product->dosage_unit ?: 'ml',
                'opening_stock' => 0,
                'reorder_level' => 0,
                'order_level' => 0,
                'unit_cost' => 0,
                'is_active' => true,
                'notes' => 'Auto-created from health product master.',
                'created_by' => auth()->id(),
            ]);

            $product->forceFill([
                'inventory_item_id' => $item->id,
            ])->saveQuietly();
        });
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function administrations()
    {
        return $this->hasMany(HealthAdministration::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return str($this->type)->replace('_', ' ')->title();
    }

    public function getFrequencyLabelAttribute(): string
    {
        return str($this->frequency)->replace('_', ' ')->title();
    }

    /*  public function calculateNextDueDate($administeredAt): ?Carbon
      {
          if (! $administeredAt) {
              return null;
          }

          $date = Carbon::parse($administeredAt);

          return match ($this->frequency) {
              'monthly' => $date->copy()->addMonthNoOverflow(),
              'quarterly' => $date->copy()->addMonthsNoOverflow(3),
              'semi_annually' => $date->copy()->addMonthsNoOverflow(6),
              'annually', 'yearly' => $date->copy()->addYearNoOverflow(),
              'custom' => $this->frequency_days
                  ? $date->copy()->addDays((int) $this->frequency_days)
                  : null,
              default => null,
          };
      }*/

    public function calculateNextDueDate($administeredAt): ?\Carbon\Carbon
    {
        if (!$administeredAt) {
            return null;
        }

        $date = \Carbon\Carbon::parse($administeredAt);

        return match ($this->frequency) {
            'monthly' => $date->copy()->addMonthNoOverflow(),
            'quarterly' => $date->copy()->addMonthsNoOverflow(3),
            'semi_annually' => $date->copy()->addMonthsNoOverflow(6),
            'annually', 'yearly' => $date->copy()->addYearNoOverflow(),
            'custom' => $this->frequency_days
                ? $date->copy()->addDays((int) $this->frequency_days)
                : null,
            default => null,
        };
    }
}
