<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingJournalEntryLine extends Model
{
    use HasFactory;

    protected $table = 'accounting_journal_entry_lines';

    protected $guarded = [];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingJournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(AccountingCostCenter::class, 'cost_center_id');
    }

    public function projectFund(): BelongsTo
    {
        return $this->belongsTo(AccountingProjectFund::class, 'project_fund_id');
    }

    public function hasAmount(): bool
    {
        return (float) $this->debit > 0 || (float) $this->credit > 0;
    }
}
