<?php

namespace App\Models\Sales;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'income_category_id',
        'invoice_date',
        'due_date',
        'sale_type',
        'status',
        'payment_status',
        'total_animals',
        'total_weight',
        'average_weight',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'other_charges_amount',
        'grand_total',
        'amount_paid',
        'balance_due',
        'other_charges_description',
        'notes',
        'terms',
        'created_by',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_animals' => 'integer',
        'total_weight' => 'decimal:2',
        'average_weight' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'other_charges_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesInvoice $invoice): void {
            if (blank($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->income_category_id);
            }

            if (blank($invoice->invoice_date)) {
                $invoice->invoice_date = now('Africa/Nairobi')->toDateString();
            }

            if (auth()->check()) {
                $invoice->created_by = auth()->id();
            }
        });

        static::saving(function (SalesInvoice $invoice): void {
            $invoice->total_animals = (int) ($invoice->total_animals ?? 0);
            $invoice->total_weight = (float) ($invoice->total_weight ?? 0);
            $invoice->average_weight = (float) ($invoice->average_weight ?? 0);
            $invoice->subtotal = (float) ($invoice->subtotal ?? 0);
            $invoice->discount_amount = (float) ($invoice->discount_amount ?? 0);
            $invoice->tax_amount = (float) ($invoice->tax_amount ?? 0);
            $invoice->other_charges_amount = (float) ($invoice->other_charges_amount ?? 0);
            $invoice->grand_total = (float) ($invoice->grand_total ?? 0);
            $invoice->amount_paid = (float) ($invoice->amount_paid ?? 0);

            $invoice->balance_due = max(
                0,
                (float) $invoice->grand_total - (float) $invoice->amount_paid
            );

            $invoice->payment_status = static::resolvePaymentStatus(
                (float) $invoice->amount_paid,
                (float) $invoice->grand_total
            );
        });

        /* static::saved(function (SalesInvoice $invoice): void {
             $invoice->markAnimalsAsSold();
         });*/
        static::saved(function (SalesInvoice $invoice): void {
            $invoice->syncAnimalSaleStatus();
        });
    }

    public static function generateInvoiceNumber(?int $incomeCategoryId = null): string
    {
        $year = now('Africa/Nairobi')->format('Y');
        $prefix = 'INV';

        if ($incomeCategoryId) {
            $category = IncomeCategory::find($incomeCategoryId);

            if ($category?->code) {
                $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $category->code));
            } elseif ($category?->type) {
                $prefix = strtoupper(substr($category->type, 0, 3));
            }
        }

        $latestInvoice = static::withTrashed()
            ->whereYear('created_at', now('Africa/Nairobi')->year)
            ->where('invoice_number', 'like', $prefix . $year . '%')
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($latestInvoice) {
            preg_match('/(\d{5})$/', $latestInvoice->invoice_number, $matches);

            $nextNumber = isset($matches[1])
                ? ((int) $matches[1]) + 1
                : 1;
        }

        return $prefix . $year . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public static function resolvePaymentStatus(float $paid, float $grandTotal): string
    {
        return match (true) {
            $grandTotal <= 0 => 'unpaid',
            $paid <= 0 => 'unpaid',
            $paid < $grandTotal => 'partial',
            $paid == $grandTotal => 'paid',
            $paid > $grandTotal => 'overpaid',
            default => 'unpaid',
        };
    }

    public function syncPaymentTotals(): void
    {
        $paid = (float) $this
            ->payments()
            ->where('status', 'successful')
            ->sum('amount');

        $grandTotal = (float) $this->grand_total;
        $balance = max(0, $grandTotal - $paid);

        $this->forceFill([
            'amount_paid' => $paid,
            'balance_due' => $balance,
            'payment_status' => static::resolvePaymentStatus($paid, $grandTotal),
        ])->saveQuietly();

        // $this->refresh();
        // $this->markAnimalsAsSold();
        $this->refresh();
        $this->syncAnimalSaleStatus();
    }

    public function syncAnimalSaleStatus(): void
    {
        $hasSuccessfulPayment = $this
            ->payments()
            ->where('status', 'successful')
            ->exists();

        $animalStatus = $hasSuccessfulPayment
            ? 'Sold'
            : 'Active';

        $this
            ->items()
            ->whereNotNull('animal_id')
            ->with('animal')
            ->get()
            ->each(function (SalesInvoiceItem $item) use ($animalStatus): void {
                $item->animal?->update([
                    'status' => $animalStatus,
                    'updated_by' => auth()->id(),
                ]);
            });
    }

    /*public function markAnimalsAsSold(): void
    {
        if (! in_array($this->status, ['issued', 'approved'], true)) {
            return;
        }

        if (! in_array($this->payment_status, ['paid', 'overpaid'], true)) {
            return;
        }

        $this->items()
            ->whereNotNull('animal_id')
            ->with('animal')
            ->get()
            ->each(function (SalesInvoiceItem $item): void {
                $item->animal?->update([
                    'status' => 'Sold',
                    'updated_by' => auth()->id(),
                ]);
            });
    }*/

    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $subtotal = (float) $items->sum('line_total');
        $totalWeight = (float) $items->sum('sale_weight');

        $totalAnimals = (int) $items
            ->where('item_type', 'animal')
            ->whereNotNull('animal_id')
            ->count();

        $discount = (float) $this->discount_amount;
        $tax = (float) $this->tax_amount;
        $other = (float) $this->other_charges_amount;

        $grandTotal = max(0, $subtotal - $discount + $tax + $other);

        $paid = (float) $this
            ->payments()
            ->where('status', 'successful')
            ->sum('amount');

        $this->forceFill([
            'subtotal' => $subtotal,
            'total_weight' => $totalWeight,
            'total_animals' => $totalAnimals,
            'average_weight' => $totalAnimals > 0 ? $totalWeight / $totalAnimals : 0,
            'grand_total' => $grandTotal,
            'amount_paid' => $paid,
            'balance_due' => max(0, $grandTotal - $paid),
            'payment_status' => static::resolvePaymentStatus($paid, $grandTotal),
        ])->saveQuietly();

        // $this->refresh();
        // $this->markAnimalsAsSold();
        $this->refresh();
        $this->syncAnimalSaleStatus();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class, 'sales_invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalesPayment::class, 'sales_invoice_id');
    }

    public function mpesaTransactions(): HasMany
    {
        return $this->hasMany(MpesaTransaction::class, 'sales_invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function incomeCategory(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->title();
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return str($this->payment_status)->replace('_', ' ')->title();
    }

    public function getSaleTypeLabelAttribute(): string
    {
        return str($this->sale_type)->replace('_', ' ')->title();
    }
}
