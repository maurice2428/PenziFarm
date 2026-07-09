<?php

namespace App\Filament\Resources\CropInputApplicationResource\Pages;

use App\Filament\Resources\CropInputApplicationResource;
use App\Services\Crops\CropInputApplicationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCropInputApplication extends CreateRecord
{
    protected static string $resource = CropInputApplicationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CropInputApplicationService::class)->create($data);
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Crop input applied')
            ->body('Inventory stock has been reduced and a stock movement has been logged.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
