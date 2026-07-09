<?php

namespace App\Filament\Resources\AnimalTransferResource\Pages;

use App\Filament\Pages\AnimalMovementDashboard;
use App\Filament\Resources\AnimalTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnimalTransfers extends ListRecords
{
    protected static string $resource = AnimalTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label('Movement Dashboard')
                ->icon('heroicon-o-chart-bar-square')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->can('view animal movement dashboard') ?? false)
                ->url(fn (): string => AnimalMovementDashboard::getUrl()),

            Actions\CreateAction::make()
                ->label('New Transfer')
                ->visible(fn (): bool => auth()->user()?->can('create animal transfers') ?? false),
        ];
    }
}
