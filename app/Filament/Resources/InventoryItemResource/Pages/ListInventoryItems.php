<?php

namespace App\Filament\Resources\InventoryItemResource\Pages;

use App\Filament\Resources\InventoryItemResource;
use App\Services\Procurement\ProcurementInventoryItemService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInventoryItems extends ListRecords
{
    protected static string $resource =
        InventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Stock Item')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->slideOver()
                ->modalWidth('6xl')
                ->modalHeading('Register Inventory Stock Item')
                ->modalDescription(
                    'Create an item for procurement, stock receiving and '
                    . 'operational issue or deduction.'
                )
                ->modalSubmitActionLabel('Save Stock Item')
                ->form(
                    InventoryItemResource::procurementCreateSchema()
                )
                ->using(
                    fn (array $data) =>
                        app(
                            ProcurementInventoryItemService::class
                        )->create($data)
                )
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Stock item created')
                        ->body(
                            'The item is now available in Inventory and '
                            . 'Purchase Orders.'
                        )
                ),
        ];
    }
}
