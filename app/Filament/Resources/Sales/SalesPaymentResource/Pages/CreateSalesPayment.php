<?php

namespace App\Filament\Resources\Sales\SalesPaymentResource\Pages;

use App\Filament\Resources\Sales\SalesPaymentResource;
use App\Services\Mpesa\MpesaDarajaService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Throwable;

class CreateSalesPayment extends CreateRecord
{
    protected static string $resource = SalesPaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['received_by'] = auth()->id();

        if (($data['payment_method'] ?? null) === 'mpesa_stk') {
            $data['status'] = 'pending';
            $data['mpesa_receipt_number'] = null;
            $data['reference_number'] = null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->payment_method !== 'mpesa_stk') {
            return;
        }

        try {
            app(MpesaDarajaService::class)->sendStkPush($this->record);

            $this->record->refresh();

            Notification::make()
                ->title('STK Push sent')
                ->body('Waiting for customer to enter M-Pesa PIN. Use Check Status to confirm after callback.')
                ->success()
                ->send();

        } catch (Throwable $e) {
            $this->record->update([
                'status' => 'failed',
                'notes' => trim(($this->record->notes ?? '') . "\nSTK Error: " . $e->getMessage()),
            ]);

            Notification::make()
                ->title('STK Push failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
