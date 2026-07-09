<?php

namespace App\Filament\Resources\CasualPayrollResource\Pages;

use App\Filament\Resources\CasualPayrollResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Livewire\Attributes\On;

class EditCasualPayroll extends EditRecord
{
    protected static string $resource = CasualPayrollResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()?->can('delete casual payroll')),
        ];
    }

    #[On('casual-payroll-totals-updated')]
    public function refreshPayrollTotals(): void
    {
        $this->record->refresh();

        $currentState = $this->form->getRawState();

        if ($currentState instanceof \Illuminate\Support\Collection) {
            $currentState = $currentState->toArray();
        }

        $this->form->fill(array_merge((array) $currentState, [
            'total_casuals' => $this->record->total_casuals ?? 0,
            'total_days_worked' => $this->record->total_days_worked ?? 0,
            'total_amount' => $this->record->total_amount ?? 0,
        ]));
    }
}
