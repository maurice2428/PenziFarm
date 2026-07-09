<?php

namespace App\Filament\Resources\Sales\SalesPaymentResource\Pages;

use App\Filament\Resources\Sales\SalesPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesPayment extends ViewRecord
{
    protected static string $resource = SalesPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->can('edit sales payments') ?? false),
        ];
    }
}
