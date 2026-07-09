<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProcurementOverdueInvoicesWidget extends BaseWidget
{
    protected static ?string $heading = 'Overdue Supplier Invoices';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public static function canView(): bool
    {
        return auth()->user()?->can('view procurement dashboard') ?? false;
    }

    protected function range(): array
    {
        return [
            $this->dateFrom ?: now('Africa/Nairobi')->startOfMonth()->toDateString(),
            $this->dateTo ?: now('Africa/Nairobi')->endOfMonth()->toDateString(),
        ];
    }

    public function table(Table $table): Table
    {
        [$from, $to] = $this->range();

        return $table
            ->query(
                PurchaseOrder::query()
                    ->with('supplier')
                    ->whereNull('deleted_at')
                    ->where('balance_due', '>', 0)
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now('Africa/Nairobi')->toDateString())
                    ->whereBetween('due_date', [$from, $to])
                    ->orderBy('due_date')
            )
            ->recordUrl(fn (PurchaseOrder $record): string =>
                PurchaseOrderResource::getUrl('edit', ['record' => $record])
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-o-document-text'),

                Tables\Columns\TextColumn::make('purchase_order_number')
                    ->label('PO No.')
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-o-clipboard-document-list'),

                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date('d M Y')
                    ->color('danger')
                    ->icon('heroicon-o-clock'),

                Tables\Columns\TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('KES')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (PurchaseOrder $record): string =>
                        PurchaseOrderResource::getUrl('edit', ['record' => $record])
                    ),
            ])
            ->paginated([5, 10, 25]);
    }
}
