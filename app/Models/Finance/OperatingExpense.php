<?php

namespace App\Models\Finance;

use App\Models\Accounting\AccountingCostCenter;
use App\Models\Accounting\AccountingProjectFund;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperatingExpense extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'expense_date' => 'date',
        'due_date' => 'date',
        'vat_claimable' => 'boolean',
        'vat_rate' => 'decimal:4',
        'withholding_tax_rate' => 'decimal:4',
        'net_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'withholding_tax_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'approved_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $expense): void {
            if (blank($expense->expense_number)) {
                $next = ((int) static::withTrashed()->max('id')) + 1;

                $expense->expense_number =
                    'EXP-'
                    . now('Africa/Nairobi')->format('Ymd')
                    . '-'
                    . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }

            $expense->created_by ??= auth()->id();
            $expense->expense_date ??= now('Africa/Nairobi')->toDateString();
        });

        static::saving(function (self $expense): void {
            $net = max(0, (float) $expense->net_amount);
            $vat = $expense->tax_treatment === 'standard_vat'
                ? round($net * ((float) $expense->vat_rate / 100), 2)
                : 0.0;
            $wht = round(
                $net * ((float) $expense->withholding_tax_rate / 100),
                2
            );
            $gross = round($net + $vat, 2);
            $payable = round(max(0, $gross - $wht), 2);

            $expense->vat_amount = $vat;
            $expense->withholding_tax_amount = $wht;
            $expense->gross_amount = $gross;
            $expense->payable_amount = $payable;
            $expense->balance_due = round(
                max(0, $payable - (float) $expense->paid_amount),
                2
            );
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            ExpenseCategory::class,
            'expense_category_id'
        )->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(
            AccountingCostCenter::class,
            'cost_center_id'
        )->withTrashed();
    }

    public function projectFund(): BelongsTo
    {
        return $this->belongsTo(
            AccountingProjectFund::class,
            'project_fund_id'
        )->withTrashed();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            OperatingExpensePayment::class,
            'operating_expense_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return in_array(
            $this->status,
            ['approved', 'partially_paid', 'paid'],
            true
        );
    }

    public function hasPostedPayments(): bool
    {
        return $this->payments()
            ->where('status', 'posted')
            ->exists();
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status ?: 'draft')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
