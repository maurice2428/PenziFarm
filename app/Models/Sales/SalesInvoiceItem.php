<?php

namespace App\Models\Sales;

use App\Models\Animal;
use App\Models\Breed;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'item_type',
        'animal_id',
        'breed_id',
        'tag_number',
        'breed_name',
        'sex',
        'description',
        'price_mode',
        'quantity',
        'sale_weight',
        'unit_price',
        'breeder_premium_amount',
        'line_total',
        'is_breeder_sale',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'sale_weight' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'breeder_premium_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'is_breeder_sale' => 'boolean',
    ];

    /*protected static function booted(): void
    {
        static::saving(function (SalesInvoiceItem $item) {
            $baseTotal = match ($item->price_mode) {
                'per_kg' => (float) $item->sale_weight * (float) $item->unit_price,
                default => (float) $item->quantity * (float) $item->unit_price,
            };

            $item->line_total = $baseTotal + (float) $item->breeder_premium_amount;
            $item->is_breeder_sale = $item->price_mode === 'breeder';
        });

        static::saved(function (SalesInvoiceItem $item) {
            $item->invoice?->recalculateTotals();
        });

        static::deleted(function (SalesInvoiceItem $item) {
            $item->invoice?->recalculateTotals();
        });
    }*/
    protected static function booted(): void
    {
        static::saving(function (SalesInvoiceItem $item) {
            $item->quantity = 1;

            $baseTotal = match ($item->price_mode) {
                'per_kg' => (float) $item->sale_weight * (float) $item->unit_price,
                default => (float) $item->unit_price,
            };

            $item->line_total = $baseTotal + (float) $item->breeder_premium_amount;
            $item->is_breeder_sale = $item->price_mode === 'breeder';
        });

        static::saved(fn(SalesInvoiceItem $item) => $item->invoice?->recalculateTotals());
        static::deleted(fn(SalesInvoiceItem $item) => $item->invoice?->recalculateTotals());
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }
}
