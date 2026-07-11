<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperatingExpensePayment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            if (blank($payment->payment_number)) {
                $next = ((int) static::withTrashed()->max('id')) + 1;

                $payment->payment_number =
                    'EXP-PAY-'
                    . now('Africa/Nairobi')->format('Ymd')
                    . '-'
                    . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }

            $payment->created_by ??= auth()->id();
            $payment->payment_date ??= now('Africa/Nairobi');
        });
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(
            OperatingExpense::class,
            'operating_expense_id'
        )->withTrashed();
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
}
