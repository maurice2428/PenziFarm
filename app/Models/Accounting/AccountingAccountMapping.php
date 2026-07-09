<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingAccountMapping extends Model
{
    use HasFactory;

    protected $table = 'accounting_account_mappings';

    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }
}
