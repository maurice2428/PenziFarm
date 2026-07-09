<?php

namespace App\Filament\Resources\FarmFieldResource\Pages;

use App\Filament\Resources\FarmFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFarmFields extends ListRecords
{
    protected static string $resource = FarmFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Field / Land Area')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
