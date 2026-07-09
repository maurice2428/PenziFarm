<?php

namespace App\Models\Sales;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_number',
        'name',
        'phone',
        'email',
        'kra_pin',
        'id_number',
        'customer_type',
        'country',
        'county',
        'town',
        'address',
        'latitude',
        'longitude',
        'place_label',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCustomerTypeLabelAttribute(): string
    {
        return match ($this->customer_type) {
            'individual' => 'Individual',
            'company' => 'Company',
            'farm' => 'Farm',
            'butcher' => 'Butcher',
            'broker' => 'Broker',
            'institution' => 'Institution',
            default => 'Other',
        };
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->customer_number})";
    }
}
