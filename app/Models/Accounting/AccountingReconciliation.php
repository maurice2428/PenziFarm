<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingReconciliation extends Model
{
    use HasFactory;

    protected $table = 'accounting_reconciliations';
    protected $guarded = [];

    protected $casts = [
        'statement_date' => 'date',
        'opening_balance' => 'decimal:2',
        'statement_balance' => 'decimal:2',
        'system_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'approved_at' => 'datetime',
        'reconciled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $reconciliation): void {
            if (blank($reconciliation->reconciliation_number)) {
                $next = (int) static::query()->max('id') + 1;
                $reconciliation->reconciliation_number =
                    'REC-'
                    . now('Africa/Nairobi')->format('Ymd')
                    . '-'
                    . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }

            if (blank($reconciliation->created_by) && auth()->check()) {
                $reconciliation->created_by = auth()->id();
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id')->withTrashed();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingReconciliationLine::class, 'reconciliation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
