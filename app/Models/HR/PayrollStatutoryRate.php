<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollStatutoryRate extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'employee_rate' => 'decimal:4',
        'employer_rate' => 'decimal:4',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'lower_earning_limit' => 'decimal:2',
        'upper_earning_limit' => 'decimal:2',
        'personal_relief' => 'decimal:2',
        'remittance_due_day' => 'integer',
        'bands' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeEffectiveOn(
        Builder $query,
        mixed $date
    ): Builder {
        return $query
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
