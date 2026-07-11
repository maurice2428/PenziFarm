<?php

namespace App\Filament\Resources\HR\PayrollResource\Pages;

use App\Enums\PayrollStatus;
use App\Filament\Resources\HR\PayrollResource;
use App\Models\HR\Payroll;
use App\Services\HR\Payroll\PayrollGenerationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreatePayroll extends CreateRecord
{
    protected static string $resource =
        PayrollResource::class;

    public function mount(): void
    {
        parent::mount();

        $periodStart = request()->query(
            'period_start'
        );

        $periodEnd = request()->query(
            'period_end'
        );

        if ($periodStart || $periodEnd) {
            $this->form->fill([
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'draft',
                'notes' => request()->query(
                    'notes'
                ),
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $periodStart = Carbon::parse(
            $data['period_start']
        );

        $data['month'] =
            (int) $periodStart->format('m');

        $data['year'] =
            (int) $periodStart->format('Y');

        $data['generated_by'] = auth()->id();

        /*
         * Only one active payroll is allowed for a month.
         * Archived runs are intentionally ignored here because they are
         * historical revisions and no longer block the new active run.
         */
        $activePayroll = Payroll::query()
            ->where(
                'month',
                $data['month']
            )
            ->where(
                'year',
                $data['year']
            )
            ->first();

        if ($activePayroll) {
            throw ValidationException::withMessages([
                'period_start' =>
                    'An active payroll already exists for '
                    . $periodStart->format('F Y')
                    . ' ('
                    . $activePayroll->revision_label
                    . '). Open, archive, or reverse that payroll '
                    . 'before creating another active run.',
            ]);
        }

        $data['revision'] =
            ((int) Payroll::query()
                ->withTrashed()
                ->where(
                    'month',
                    $data['month']
                )
                ->where(
                    'year',
                    $data['year']
                )
                ->max('revision'))
            + 1;

        return $data;
    }

    protected function handleRecordCreation(
        array $data
    ): Model {
        try {
            return static::getModel()::create(
                $data
            );
        } catch (
            UniqueConstraintViolationException $exception
        ) {
            report($exception);

            throw ValidationException::withMessages([
                'period_start' =>
                    'This payroll period is already occupied. '
                    . 'Run the payroll-period revision migration, '
                    . 'then retry. The system will preserve the archived '
                    . 'run and create a new revision.',
            ]);
        }
    }

    protected function afterCreate(): void
    {
        $status = $this->record->status
            instanceof PayrollStatus
                ? $this->record->status->value
                : (string) $this->record->status;

        if ($status === 'generated') {
            app(PayrollGenerationService::class)
                ->generate($this->record);

            Notification::make()
                ->success()
                ->title(
                    'Payroll created and generated'
                )
                ->body(
                    $this->record->period_label
                    . ' was created successfully. Payroll items '
                    . 'and payslips were generated for active staff.'
                )
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Payroll created')
            ->body(
                $this->record->period_label
                . ' was saved as a draft.'
            )
            ->send();
    }
}
