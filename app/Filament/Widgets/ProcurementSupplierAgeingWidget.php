<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SupplierResource;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProcurementSupplierAgeingWidget extends BaseWidget
{
    protected static ?string $heading = 'Supplier Ageing Buckets';

    protected static ?string $description = 'Accounts payable ageing: suppliers have delivered/invoiced us, and these are the balances we still owe.';

    protected static ?int $sort = 8;

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

    protected function basePayableQuery(Supplier $supplier)
    {
        [$from, $to] = $this->range();

        return PurchaseOrder::query()
            ->whereNull('deleted_at')
            ->where('supplier_id', $supplier->id)
            ->where('balance_due', '>', 0)
            ->whereBetween('order_date', [$from, $to]);
    }

    protected function bucketAmount(Supplier $supplier, string $bucket): float
    {
        $today = now('Africa/Nairobi')->toDateString();

        $query = $this->basePayableQuery($supplier);

        match ($bucket) {
            'current' => $query->where(function ($q) use ($today) {
                $q->whereNull('due_date')
                    ->orWhereDate('due_date', '>=', $today);
            }),

            '0_7' => $query
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [
                    now('Africa/Nairobi')->subDays(7)->toDateString(),
                    now('Africa/Nairobi')->subDay()->toDateString(),
                ]),

            '8_30' => $query
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [
                    now('Africa/Nairobi')->subDays(30)->toDateString(),
                    now('Africa/Nairobi')->subDays(8)->toDateString(),
                ]),

            '31_60' => $query
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [
                    now('Africa/Nairobi')->subDays(60)->toDateString(),
                    now('Africa/Nairobi')->subDays(31)->toDateString(),
                ]),

            '60_plus' => $query
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<=', now('Africa/Nairobi')->subDays(61)->toDateString()),

            default => null,
        };

        return (float) $query->sum('balance_due');
    }

    protected function totalPayable(Supplier $supplier): float
    {
        return (float) $this->basePayableQuery($supplier)->sum('balance_due');
    }

    public function table(Table $table): Table
    {
        [$from, $to] = $this->range();

        return $table
            ->query(
                Supplier::query()
                    ->whereHas('purchaseOrders', function ($query) use ($from, $to) {
                        $query
                            ->whereNull('deleted_at')
                            ->where('balance_due', '>', 0)
                            ->whereBetween('order_date', [$from, $to]);
                    })
                    ->orderBy('company_name')
            )
            ->recordUrl(fn (): string => SupplierResource::getUrl('index'))
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-storefront'),

                Tables\Columns\TextColumn::make('current_bucket')
                    ->label('Current')
                    ->state(fn (Supplier $record) => 'KES ' . number_format($this->bucketAmount($record, 'current'), 2))
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('bucket_0_7')
                    ->label('0–7')
                    ->state(fn (Supplier $record) => 'KES ' . number_format($this->bucketAmount($record, '0_7'), 2))
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('bucket_8_30')
                    ->label('8–30')
                    ->state(fn (Supplier $record) => 'KES ' . number_format($this->bucketAmount($record, '8_30'), 2))
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('bucket_31_60')
                    ->label('31–60')
                    ->state(fn (Supplier $record) => 'KES ' . number_format($this->bucketAmount($record, '31_60'), 2))
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('bucket_60_plus')
                    ->label('60+')
                    ->state(fn (Supplier $record) => 'KES ' . number_format($this->bucketAmount($record, '60_plus'), 2))
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('total_payable')
                    ->label('Total Owed')
                    ->state(fn (Supplier $record) => 'KES ' . number_format($this->totalPayable($record), 2))
                    ->weight('bold')
                    ->color('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('statement')
                    ->label('Statement')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Supplier $record) => route('procurement.suppliers.statement', [
                        'supplier' => $record,
                        'from' => $this->dateFrom,
                        'to' => $this->dateTo,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->paginated([5, 10, 25]);
    }
}
