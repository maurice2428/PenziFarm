<?php

namespace App\Filament\Resources\Projects\FarmProjectResource\Pages;

use App\Filament\Resources\Projects\FarmProjectResource;
use App\Services\Projects\ProjectFinancialService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFarmProject extends EditRecord
{
    protected static string $resource = FarmProjectResource::class;

    protected function afterSave(): void
    {
        app(ProjectFinancialService::class)->recalculate($this->record);
        app(ProjectFinancialService::class)->recalculateProgress($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
