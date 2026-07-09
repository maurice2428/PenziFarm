<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrderPayment;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected array $initialPayment = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialPayment = [
            'record' => (bool) ($data['record_initial_payment'] ?? false),
            'payment_date' => $data['initial_payment_date'] ?? now('Africa/Nairobi')->toDateString(),
            'amount' => (float) ($data['initial_payment_amount'] ?? 0),
            'payment_method' => $data['initial_payment_method'] ?? null,
            'mpesa_reference' => $data['initial_mpesa_reference'] ?? null,
            'bank_name' => $data['initial_bank_name'] ?? null,
            'bank_reference' => $data['initial_bank_reference'] ?? null,
            'cheque_number' => $data['initial_cheque_number'] ?? null,
            'notes' => $data['initial_payment_notes'] ?? null,
        ];

        $items = $data['items'] ?? [];

        $subtotal = 0;
        $taxAmount = 0;
        $itemDiscounts = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['quantity_ordered'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $discount = (float) ($item['discount_amount'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineSubtotal = $qty * $unitCost;
            $taxableAmount = max(0, $lineSubtotal - $discount);
            $lineTax = $taxableAmount * ($taxRate / 100);

            $subtotal += $lineSubtotal;
            $taxAmount += $lineTax;
            $itemDiscounts += $discount;
        }

        $orderDiscount = (float) ($data['discount_amount'] ?? 0);
        $otherCharges = (float) ($data['other_charges'] ?? 0);

        $grandTotal = max(
            0,
            $subtotal + $taxAmount + $otherCharges - $itemDiscounts - $orderDiscount
        );

        $data['subtotal'] = round($subtotal, 2);
        $data['tax_amount'] = round($taxAmount, 2);
        $data['grand_total'] = round($grandTotal, 2);
        $data['amount_paid'] = 0;
        $data['balance_due'] = round($grandTotal, 2);
        $data['payment_status'] = 'unpaid';

        unset(
            $data['record_initial_payment'],
            $data['initial_payment_date'],
            $data['initial_payment_amount'],
            $data['initial_payment_method'],
            $data['initial_mpesa_reference'],
            $data['initial_bank_name'],
            $data['initial_bank_reference'],
            $data['initial_cheque_number'],
            $data['initial_payment_notes']
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->load('items');
        $this->record->recalculateTotals();

        if (
            ($this->initialPayment['record'] ?? false) &&
            ($this->initialPayment['amount'] ?? 0) > 0 &&
            filled($this->initialPayment['payment_method'] ?? null)
        ) {
            $amount = min(
                (float) $this->initialPayment['amount'],
                (float) $this->record->grand_total
            );

            PurchaseOrderPayment::create([
                'purchase_order_id' => $this->record->id,
                'payment_date' => $this->initialPayment['payment_date'],
                'amount' => $amount,
                'payment_method' => $this->initialPayment['payment_method'],
                'status' => 'successful',
                'mpesa_reference' => $this->initialPayment['mpesa_reference'],
                'bank_name' => $this->initialPayment['bank_name'],
                'bank_reference' => $this->initialPayment['bank_reference'],
                'cheque_number' => $this->initialPayment['cheque_number'],
                'notes' => $this->initialPayment['notes'],
            ]);

            $this->record->refresh();
            $this->record->syncPaymentTotals();

            Notification::make()
                ->title('Purchase order and supplier payment recorded')
                ->body('The initial payment has been linked to this procurement invoice.')
                ->success()
                ->send();

            return;
        }

        $this->record->syncPaymentTotals();

        Notification::make()
            ->title('Purchase order created')
            ->body('You can pay this invoice later using the Pay Invoice action.')
            ->success()
            ->send();
    }
}
