<?php

namespace App\Filament\Resources\HR\PayrollResource\Pages;

use App\Filament\Resources\HR\PayrollResource;
use App\Services\HR\Payroll\PayrollLifecycleService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPayroll extends EditRecord
{
    protected static string $resource =
        PayrollResource::class;

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        abort_if(
            $this->record->trashed(),
            403,
            'Archived payrolls cannot be edited.'
        );

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('deletePayroll')
                ->label('Delete / Reverse Payroll')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(
                    fn (): bool =>
                        ! $this->record->trashed()
                        && (
                            auth()->user()?->can(
                                'delete payroll'
                            )
                            ?? false
                        )
                )
                ->requiresConfirmation()
                ->modalHeading(
                    'Reverse and archive this payroll?'
                )
                ->modalDescription(
                    'All posted payroll payments, remittances and '
                    . 'accounting entries will be reversed. Generated '
                    . 'payslips will be removed.'
                )
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->required()
                        ->minLength(5)
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(PayrollLifecycleService::class)
                        ->archiveAndReverse(
                            $this->record,
                            $data['reason']
                        );

                    Notification::make()
                        ->warning()
                        ->title(
                            'Payroll reversed and archived'
                        )
                        ->send();

                    $this->redirect(
                        static::getResource()::getUrl('index')
                    );
                }),
        ];
    }
}
