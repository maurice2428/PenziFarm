<?php

namespace App\Filament\Resources\Sales\SalesInvoiceResource\Pages;

use App\Filament\Resources\Sales\SalesInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesInvoice extends ViewRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->visible(fn () => auth()->user()?->can('edit sales invoices') ?? false),
        ];
    }
}
