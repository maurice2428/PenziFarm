<?php

namespace App\Filament\Resources\HealthProductResource\Pages;

use App\Filament\Resources\HealthProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHealthProducts extends ListRecords
{
    protected static string $resource = HealthProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Health Product')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->slideOver()
                ->modalWidth('6xl'),
        ];
    }
}
