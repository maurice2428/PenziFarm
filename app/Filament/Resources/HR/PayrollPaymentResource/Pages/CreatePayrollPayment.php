<?php

namespace App\Filament\Resources\HR\PayrollPaymentResource\Pages;

use App\Filament\Resources\HR\PayrollPaymentResource;
use App\Models\HR\Payroll;
use App\Services\HR\Payroll\PayrollPaymentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePayrollPayment extends CreateRecord
{
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

    protected static string $resource =
        PayrollPaymentResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $payroll = Payroll::query()
            ->with('items')
            ->findOrFail($data['payroll_id']);

        if (! $payroll->canReceivePayments()) {
            throw ValidationException::withMessages([
                'payroll_id' =>
                    'Only approved or posted payrolls with '
                    . 'an outstanding salary balance can be paid.',
            ]);
        }

        $rows = collect(
            $this->paymentRows()
        )
            ->filter(
                fn (array $row): bool =>
                    filled(
                        $row['payroll_item_id']
                        ?? null
                    )
                    && (float) (
                        $row['amount'] ?? 0
                    ) > 0
            )
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'items' =>
                    'The selected payroll has no payable '
                    . 'employee salary lines.',
            ]);
        }

        $validItemIds = $payroll->items
            ->pluck('id')
            ->map(
                fn ($id): int => (int) $id
            );

        foreach ($rows as $row) {
            if (
                ! $validItemIds->contains(
                    (int) $row['payroll_item_id']
                )
            ) {
                throw ValidationException::withMessages([
                    'items' =>
                        'One employee payment line does not '
                        . 'belong to the selected payroll.',
                ]);
            }
        }

        $data['total_amount'] = round(
            (float) $rows->sum(
                fn (array $row): float =>
                    (float) $row['amount']
            ),
            2
        );

        $data['status'] = 'draft';

        unset($data['items']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(PayrollPaymentService::class)
            ->syncDraftItemsFromForm(
                $this->record,
                $this->paymentRows()
            );

        Notification::make()
            ->success()
            ->title(
                'Salary payment draft created'
            )
            ->body(
                'The employee lines, statutory figures and '
                . 'saved payment destinations were copied from '
                . 'the selected payroll.'
            )
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'edit',
            ['record' => $this->record]
        );
    }
}
