<?php

namespace App\Filament\Resources\Sales\SalesInvoiceResource\Pages;

use App\Filament\Resources\Sales\SalesInvoiceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesInvoice extends CreateRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    public ?string $soldAnimalTag = null;

    public ?int $soldAnimalId = null;

    public function mount(): void
    {
        parent::mount();

        $this->soldAnimalTag = request()->query('sold_animal_tag');
        $this->soldAnimalId = request()->integer('sold_animal_id') ?: null;

        if ($this->soldAnimalTag && request()->query('sold_notice') == 1) {
            Notification::make()
                ->title('Before Proceeding')
                ->body('Copy this Tag Number before creating the invoice: ' . $this->soldAnimalTag)
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->prepareTotals($data);
    }

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
        $this->record->refresh();

        /*
        |--------------------------------------------------------------------------
        | Now mark animals as sold after invoice has been generated.
        |--------------------------------------------------------------------------
        */
       // $this->record->markAnimalsAsSold();
       $this->record->syncAnimalSaleStatus();
    }

    private function prepareTotals(array $data): array
    {
        $items = $data['items'] ?? [];

        $totalAnimals = 0;
        $totalWeight = 0;
        $subtotal = 0;

        foreach ($items as $item) {
            if (($item['item_type'] ?? null) === 'animal') {
                $totalAnimals++;
            }

            $weight = (float) ($item['sale_weight'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $premium = (float) ($item['breeder_premium_amount'] ?? 0);
            $priceMode = $item['price_mode'] ?? 'fixed';

            $lineTotal = $priceMode === 'per_kg'
                ? $weight * $unitPrice
                : $unitPrice;

            $lineTotal += $premium;

            $totalWeight += $weight;
            $subtotal += $lineTotal;
        }

        $discount = (float) ($data['discount_amount'] ?? 0);
        $tax = (float) ($data['tax_amount'] ?? 0);
        $other = (float) ($data['other_charges_amount'] ?? 0);
        $paid = (float) ($data['amount_paid'] ?? 0);

        $grandTotal = max(0, $subtotal - $discount + $tax + $other);

        $data['total_animals'] = $totalAnimals;
        $data['total_weight'] = $totalWeight;
        $data['average_weight'] = $totalAnimals > 0 ? $totalWeight / $totalAnimals : 0;
        $data['subtotal'] = $subtotal;
        $data['grand_total'] = $grandTotal;
        $data['balance_due'] = max(0, $grandTotal - $paid);

        return $data;
    }
}
