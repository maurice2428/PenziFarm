<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'adjustment_no',
        'adjustment_date',
        'reason',
        'total_in_quantity',
        'total_out_quantity',
        'total_value',
        'notes',
        'adjusted_by',
        'created_by',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'total_in_quantity' => 'decimal:3',
        'total_out_quantity' => 'decimal:3',
        'total_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockAdjustment $record): void {
            if (blank($record->adjustment_no)) {
                $record->adjustment_no =
                    'ADJ' .
                    now('Africa/Nairobi')->format('Ymd') .
                    str_pad((string) ((int) static::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
            }

            if (blank($record->adjustment_date)) {
                $record->adjustment_date = now('Africa/Nairobi')->toDateString();
            }

            if (auth()->check()) {
                $record->created_by ??= auth()->id();
                $record->adjusted_by ??= auth()->id();
            }
        });
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'referenceable');
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return str($this->reason ?: 'manual_correction')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
