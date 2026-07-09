<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'accounting_accounts';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_INCOME = 'income';
    public const TYPE_COST_OF_SALES = 'cost_of_sales';
    public const TYPE_EXPENSE = 'expense';

    public const NORMAL_DEBIT = 'debit';
    public const NORMAL_CREDIT = 'credit';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_ASSET => 'Assets',
            self::TYPE_LIABILITY => 'Liabilities',
            self::TYPE_EQUITY => 'Equity',
            self::TYPE_INCOME => 'Income',
            self::TYPE_COST_OF_SALES => 'Cost of Sales',
            self::TYPE_EXPENSE => 'Expenses',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('code');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(AccountingJournalEntryLine::class, 'account_id');
    }

    public function openingBalances(): HasMany
    {
        return $this->hasMany(AccountingOpeningBalance::class, 'account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLeaf(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    public function isDebitNormal(): bool
    {
        return $this->normal_balance === self::NORMAL_DEBIT;
    }

    public function signedBalance(float|int|string $debits, float|int|string $credits): float
    {
        $debit = (float) $debits;
        $credit = (float) $credits;

        return $this->isDebitNormal()
            ? round($debit - $credit, 2)
            : round($credit - $debit, 2);
    }
}
