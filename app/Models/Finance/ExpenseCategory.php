<?php

namespace App\Models\Finance;

use App\Models\Accounting\AccountingAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'default_wht_rate' => 'decimal:4',
        'requires_etims' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            AccountingAccount::class,
            'account_id'
        )->withTrashed();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(
            OperatingExpense::class,
            'expense_category_id'
        );
    }

    public function canBeDeletedSafely(): bool
    {
        return ! $this->expenses()->withTrashed()->exists();
    }
}
