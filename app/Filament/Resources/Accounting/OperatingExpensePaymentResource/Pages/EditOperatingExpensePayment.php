<?php
namespace App\Filament\Resources\Accounting\OperatingExpensePaymentResource\Pages;
use App\Filament\Resources\Accounting\OperatingExpensePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditOperatingExpensePayment extends EditRecord
{
    protected static string $resource = OperatingExpensePaymentResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        abort_unless($this->record->status === 'draft', 403, 'Posted payments cannot be edited.');
        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->visible(fn (): bool => $this->record->status === 'draft')];
    }
}
