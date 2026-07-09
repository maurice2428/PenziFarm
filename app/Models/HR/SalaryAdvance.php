<?php

namespace App\Models\HR;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'request_date',
        'amount_requested',
        'amount_approved',
        'reason',
        'repayment_mode',
        'repayment_months',
        'monthly_deduction',
        'approval_status',
        'approved_by',
        'approved_at',
        'balance',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'amount_requested' => 'decimal:2',
        'amount_approved' => 'decimal:2',
        'monthly_deduction' => 'decimal:2',
        'balance' => 'decimal:2',
        'approved_at' => 'datetime',
        'approval_status' => ApprovalStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return $this->approval_status instanceof ApprovalStatus
            ? ucfirst($this->approval_status->value)
            : ucfirst((string) $this->approval_status);
    }

    public function getRepaymentModeLabelAttribute(): string
    {
        return match ((string) $this->repayment_mode) {
            'one_off' => 'One Off',
            'installments' => 'Installments',
            default => ucfirst((string) $this->repayment_mode),
        };
    }

    public function getRecoveredAmountAttribute(): float
    {
        return max(
            0,
            (float) ($this->amount_approved ?? 0) - (float) ($this->balance ?? 0)
        );
    }

    public function getIsSettledAttribute(): bool
    {
        return (float) ($this->balance ?? 0) <= 0;
    }
}
