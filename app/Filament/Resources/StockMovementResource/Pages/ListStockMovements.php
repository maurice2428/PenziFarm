<?php

namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printReport')
                ->label('Print Stock Movement Report')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn(): string => route('inventory.stock-movements.report'))
                ->openUrlInNewTab(),
        ];
    }
}
