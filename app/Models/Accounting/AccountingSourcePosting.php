<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSourcePosting extends Model
{
    use HasFactory;

    protected $table = 'accounting_source_postings';
    protected $guarded = [];

    protected $casts = [
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'attempts' => 'integer',
        'metadata' => 'array',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingJournalEntry::class, 'journal_entry_id')->withTrashed();
    }
}
