<?php

namespace App\Filament\Resources\NurseryBatchResource\Pages;

use App\Filament\Resources\NurseryBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNurseryBatches extends ListRecords
{
    protected static string $resource = NurseryBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Nursery Batch')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
