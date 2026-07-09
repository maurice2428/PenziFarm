<?php

namespace App\Filament\Resources\Sales\IncomeCategoryResource\Pages;

use App\Filament\Resources\Sales\IncomeCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIncomeCategory extends EditRecord
{
    protected static string $resource = IncomeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
