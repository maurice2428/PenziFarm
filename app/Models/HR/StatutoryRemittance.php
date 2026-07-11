<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatutoryRemittance extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'payment_date' => 'datetime',
        'amount_due' => 'decimal:2',
        'amount' => 'decimal:2',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $remittance): void {
            if (blank($remittance->remittance_number)) {
                $next = ((int) static::withTrashed()->max('id')) + 1;

                $remittance->remittance_number =
                    'STAT-'
                    . now('Africa/Nairobi')->format('Ymd')
                    . '-'
                    . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }

            $remittance->created_by ??= auth()->id();
        });
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->statutory_type) {
            'paye' => 'PAYE',
            'nssf' => 'NSSF',
            'shif' => 'SHIF',
            'housing_levy' => 'Affordable Housing Levy',
            default => str($this->statutory_type)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }
}
