<?php

namespace App\Filament\Resources\BreedingGestationRuleResource\Pages;

use App\Filament\Resources\BreedingGestationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBreedingGestationRule extends EditRecord
{
    protected static string $resource = BreedingGestationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
