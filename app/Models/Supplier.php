<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_name',
        'contact_person',
        'phone_primary',
        'phone_secondary',
        'email',
        'kra_pin',
        'physical_address',
        'bank_name',
        'bank_account',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: 'Unnamed Supplier';
    }

    public function getTotalPurchasesAttribute(): float
    {
        return (float) $this->purchaseOrders()
            ->whereNull('deleted_at')
            ->sum('grand_total');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) PurchaseOrderPayment::query()
            ->whereNull('purchase_order_payments.deleted_at')
            ->where('purchase_order_payments.status', 'successful')
            ->whereHas('purchaseOrder', function ($query) {
                $query->where('supplier_id', $this->id);
            })
            ->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->total_purchases - $this->total_paid);
    }
}
