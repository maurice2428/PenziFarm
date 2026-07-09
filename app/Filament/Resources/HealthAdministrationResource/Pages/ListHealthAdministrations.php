<?php

namespace App\Filament\Resources\HealthAdministrationResource\Pages;

use App\Filament\Resources\HealthAdministrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHealthAdministrations extends ListRecords
{
    protected static string $resource = HealthAdministrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Administration')
                ->icon('heroicon-o-plus-circle')
                ->color('success'),
        ];
    }
}
