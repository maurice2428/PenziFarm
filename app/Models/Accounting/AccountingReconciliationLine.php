<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingReconciliationLine extends Model
{
    use HasFactory;

    protected $table = 'accounting_reconciliation_lines';

    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'is_cleared' => 'boolean',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(AccountingReconciliation::class, 'reconciliation_id');
    }

    public function journalEntryLine(): BelongsTo
    {
        return $this->belongsTo(AccountingJournalEntryLine::class, 'journal_entry_line_id');
    }
}
