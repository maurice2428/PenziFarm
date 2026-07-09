<?php

namespace App\Filament\Resources\HR\PayrollResource\Pages;

use App\Enums\PayrollStatus;
use App\Filament\Resources\HR\PayrollResource;
use App\Models\HR\Payroll;
use App\Services\HR\Payroll\PayrollGenerationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $periodStart = Carbon::parse($data['period_start']);

        $data['month'] = (int) $periodStart->format('m');
        $data['year'] = (int) $periodStart->format('Y');
        $data['generated_by'] = auth()->id();

        $exists = Payroll::query()
            ->where('month', $data['month'])
            ->where('year', $data['year'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Payroll already exists')
                ->body("A payroll for {$data['month']}/{$data['year']} already exists.")
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $status = $this->record->status instanceof PayrollStatus
            ? $this->record->status->value
            : $this->record->status;

        if ($status === 'generated') {
            app(PayrollGenerationService::class)->generate($this->record);

            Notification::make()
                ->success()
                ->title('Payroll created and generated successfully')
                ->body('Payroll items and payslips have been generated for all active staff.')
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Payroll created successfully')
            ->body('Payroll saved as draft.')
            ->send();
    }
}
