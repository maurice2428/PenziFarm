<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollPayment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            if (blank($payment->payment_number)) {
                $next = ((int) static::withTrashed()->max('id')) + 1;

                $payment->payment_number =
                    'PAYROLL-PAY-'
                    . now('Africa/Nairobi')->format('Ymd')
                    . '-'
                    . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }

            $payment->created_by ??= auth()->id();
            $payment->payment_date ??= now('Africa/Nairobi');

            /*
             * The database column is NOT NULL. A draft is inserted before
             * employee payment lines are populated, so normalize a missing
             * total to zero and let PayrollPaymentService refresh it.
             */
            $payment->total_amount = round(
                (float) ($payment->total_amount ?? 0),
                2
            );
        });
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollPaymentItem::class);
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

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status ?: 'draft')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
