<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingJournalEntry extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'accounting_journal_entries';
    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'posted_at' => 'datetime',
        'approved_at' => 'datetime',
        'reversed_at' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'metadata' => 'array',
        'lock_version' => 'integer',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(AccountingFiscalYear::class, 'fiscal_year_id');
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingJournalEntryLine::class, 'journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id')->withTrashed();
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id')->withTrashed();
    }

    public function sourcePosting(): HasOne
    {
        return $this->hasOne(AccountingSourcePosting::class, 'journal_entry_id');
    }

    public function taxTransactions(): HasMany
    {
        return $this->hasMany(AccountingTaxTransaction::class, 'journal_entry_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isApproved(): bool
    {
        return filled($this->approved_at);
    }

    public function isBalanced(): bool
    {
        return abs((float) $this->total_debit - (float) $this->total_credit) < 0.01;
    }

    public function canBeDeletedSafely(): bool
    {
        return $this->isDraft() && ! $this->sourcePosting()->exists();
    }
}
