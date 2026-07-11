<?php
namespace App\Filament\Resources\HR\StatutoryRemittanceResource\Pages;
use App\Filament\Resources\HR\StatutoryRemittanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListStatutoryRemittances extends ListRecords
{
    protected static string $resource = StatutoryRemittanceResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
