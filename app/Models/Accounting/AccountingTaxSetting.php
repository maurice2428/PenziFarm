<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingTaxSetting extends Model
{
    use HasFactory;

    protected $table = 'accounting_tax_settings';

    protected $guarded = [];

    protected $casts = [
        'rate' => 'decimal:4',
        'fixed_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
