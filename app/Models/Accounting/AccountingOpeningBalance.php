<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingOpeningBalance extends Model
{
    use HasFactory;

    protected $table = 'accounting_opening_balances';
    protected $guarded = [];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(AccountingFiscalYear::class, 'fiscal_year_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id')->withTrashed();
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingJournalEntry::class, 'journal_entry_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
