<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use App\Services\Inventory\StockAdjustmentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(StockAdjustmentService::class)->create([
            ...$data,
            'items' => $this->data['items'] ?? [],
        ]);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Stock adjustment saved')
            ->body('Stock movements have been created automatically.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
