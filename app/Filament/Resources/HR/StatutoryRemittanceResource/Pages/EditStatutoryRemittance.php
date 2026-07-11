<?php
namespace App\Filament\Resources\HR\StatutoryRemittanceResource\Pages;
use App\Filament\Resources\HR\StatutoryRemittanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditStatutoryRemittance extends EditRecord
{
    protected static string $resource = StatutoryRemittanceResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        abort_unless($this->record->isDraft(), 403, 'Posted remittances cannot be edited.');
        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->visible(fn (): bool => $this->record->isDraft())];
    }
}
