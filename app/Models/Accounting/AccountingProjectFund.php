<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingProjectFund extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'accounting_project_funds';

    protected $guarded = [];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'metadata' => 'array',
    ];

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(AccountingFundingSource::class, 'funding_source_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(AccountingCostCenter::class, 'cost_center_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountingProjectFundTransaction::class, 'project_fund_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(AccountingJournalEntryLine::class, 'project_fund_id');
    }

    protected function utilizationPercent(): Attribute
    {
        return Attribute::get(function (): float {
            $budget = (float) $this->budget_amount;

            if ($budget <= 0) {
                return 0.0;
            }

            return round(((float) $this->spent_amount / $budget) * 100, 2);
        });
    }

    public function refreshBalances(): void
    {
        $received = (float) $this->transactions()
            ->whereIn('transaction_type', ['receipt', 'allocation'])
            ->sum('amount');

        $spent = (float) $this->transactions()
            ->where('transaction_type', 'expense')
            ->sum('amount');

        $refunds = (float) $this->transactions()
            ->where('transaction_type', 'refund')
            ->sum('amount');

        $adjustments = (float) $this->transactions()
            ->where('transaction_type', 'adjustment')
            ->sum('amount');

        $this->forceFill([
            'received_amount' => round($received, 2),
            'spent_amount' => round($spent, 2),
            'balance_amount' => round($received - $spent - $refunds + $adjustments, 2),
        ])->save();
    }
}
