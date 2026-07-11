<?php
namespace App\Filament\Resources\Accounting\OperatingExpenseResource\Pages;
use App\Filament\Resources\Accounting\OperatingExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditOperatingExpense extends EditRecord
{
    protected static string $resource = OperatingExpenseResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        abort_unless($this->record->isDraft(), 403, 'Approved expenses cannot be edited. Reverse them through the lifecycle workflow.');
        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->visible(fn (): bool => $this->record->isDraft())];
    }
}
