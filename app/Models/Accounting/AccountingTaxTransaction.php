<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingTaxTransaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'accounting_tax_transactions';
    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'tax_point_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'tax_rate' => 'decimal:4',
        'taxable_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function taxSetting(): BelongsTo
    {
        return $this->belongsTo(AccountingTaxSetting::class, 'tax_setting_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingJournalEntry::class, 'journal_entry_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
