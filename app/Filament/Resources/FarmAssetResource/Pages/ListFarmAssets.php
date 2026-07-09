<?php

namespace App\Filament\Resources\FarmAssetResource\Pages;

use App\Filament\Resources\FarmAssetResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListFarmAssets extends ListRecords
{
    protected static string $resource = FarmAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printValuationReport')
                ->label('Print Valuation Report')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn(): string => route('assets.valuation-report'))
                ->openUrlInNewTab(),
            Actions\CreateAction::make()
                ->label('New Asset')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn(): bool => static::getResource()::canCreate()),
        ];
    }
}
