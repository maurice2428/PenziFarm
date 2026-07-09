<?php

namespace App\Filament\Resources\Sales\SalesInvoiceResource\Pages;

use App\Filament\Pages\Sales\CustomerGeoIntelligence;
use App\Filament\Resources\Sales\SalesInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesInvoices extends ListRecords
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('geoIntelligence')
                ->label('Geo Intelligence')
                ->icon('heroicon-o-map')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->can('view customer geo intelligence') ?? false)
                ->url(fn (): string => CustomerGeoIntelligence::getUrl()),

            Actions\CreateAction::make()
                ->label('New Sales Invoice')
                ->visible(fn (): bool => auth()->user()?->can('create sales invoices') ?? false),
        ];
    }
}
