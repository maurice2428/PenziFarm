<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingProjectFundTransaction extends Model
{
    use HasFactory;

    protected $table = 'accounting_project_fund_transactions';

    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $transaction) => $transaction->projectFund?->refreshBalances());
        static::deleted(fn (self $transaction) => $transaction->projectFund?->refreshBalances());
    }

    public function projectFund(): BelongsTo
    {
        return $this->belongsTo(AccountingProjectFund::class, 'project_fund_id');
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(AccountingFundingSource::class, 'funding_source_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingJournalEntry::class, 'journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
