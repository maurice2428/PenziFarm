<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingPostingFailure extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'accounting_posting_failures';
    protected $guarded = [];

    protected $casts = [
        'attempts' => 'integer',
        'last_attempted_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
