<?php

namespace App\Filament\Resources\HR\PayrollPaymentResource\Pages;

use App\Filament\Resources\HR\PayrollPaymentResource;
use App\Services\HR\Payroll\PayrollPaymentService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPayrollPayment extends EditRecord
{
    protected static string $resource =
        PayrollPaymentResource::class;

    private function paymentRows(): array
    {
        $state = $this->form->getRawState();

        if (
            $state instanceof
            \Illuminate\Support\Collection
        ) {
            $state = $state->toArray();
        }

        return array_values(
            (array) (
                $state['items']
                ?? $this->data['items']
                ?? []
            )
        );
    }

    protected function mutateFormDataBeforeFill(
        array $data
    ): array {
        $service = app(
            PayrollPaymentService::class
        );

        /*
         * Older drafts may have been created before the employee
         * lines were persisted. Repair those drafts before filling
         * the form.
         */
        $this->record = $service
            ->ensureDraftItems(
                $this->record
            );

        $this->record->loadMissing(
            'payroll'
        );

        if ($this->record->payroll) {
            $data = array_merge(
                $data,
                $service->payrollSummary(
                    $this->record->payroll
                )
            );
        }

        $data['items'] =
            $service->formRowsForPayment(
                $this->record
            );

        return $data;
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        abort_unless(
            $this->record->isDraft(),
            403,
            'Posted salary payments cannot be edited.'
        );

        $rows = collect(
            $this->paymentRows()
        );

        $data['total_amount'] = round(
            (float) $rows->sum(
                fn (array $row): float =>
                    max(
                        0,
                        (float) (
                            $row['amount'] ?? 0
                        )
                    )
            ),
            2
        );

        unset($data['items']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record = app(
            PayrollPaymentService::class
        )->syncDraftItemsFromForm(
            $this->record,
            $this->paymentRows()
        );

        /*
         * Refill the enriched view data so the employee and statutory
         * snapshot remains visible after Save.
         */
        $this->fillForm();

        Notification::make()
            ->success()
            ->title(
                'Salary payment draft updated'
            )
            ->body(
                'Employee payment lines and the batch total '
                . 'were synchronized successfully.'
            )
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(
                    fn (): bool =>
                        $this->record->isDraft()
                ),
        ];
    }
}
