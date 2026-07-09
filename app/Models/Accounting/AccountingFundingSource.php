<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingFundingSource extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'accounting_funding_sources';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function linkedAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'linked_account_id');
    }

    public function projectFunds(): HasMany
    {
        return $this->hasMany(AccountingProjectFund::class, 'funding_source_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountingProjectFundTransaction::class, 'funding_source_id');
    }
}
