<?php

namespace App\Filament\Resources\Sales\SalesPaymentResource\Pages;

use App\Filament\Resources\Sales\SalesPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesPayments extends ListRecords
{
    protected static string $resource = SalesPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Record Payment')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn () => auth()->user()?->can('create sales payments') ?? false),
        ];
    }
}
