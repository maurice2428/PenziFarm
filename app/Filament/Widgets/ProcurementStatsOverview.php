<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InventoryItemResource;
use App\Filament\Resources\PurchaseOrderPaymentResource;
use App\Filament\Resources\PurchaseOrderResource;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProcurementStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public static function canView(): bool
    {
        return auth()->user()?->can('view procurement dashboard') ?? false;
    }

    protected function range(): array
    {
        $from = Carbon::parse($this->dateFrom ?: now('Africa/Nairobi')->startOfMonth())->startOfDay();
        $to = Carbon::parse($this->dateTo ?: now('Africa/Nairobi')->endOfMonth())->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    protected function previousRange(Carbon $from, Carbon $to): array
    {
        $days = max(1, $from->diffInDays($to) + 1);

        $previousTo = $from->copy()->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();

        return [$previousFrom, $previousTo];
    }

    protected function trend(float $current, float $previous): array
    {
        if ($previous <= 0 && $current > 0) {
            return [100, 'up'];
        }

        if ($previous <= 0 && $current <= 0) {
            return [0, 'flat'];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            round(abs($change), 1),
            $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
        ];
    }

    protected function trendText(float $current, float $previous, string $goodDirection = 'up'): array
    {
        [$percent, $direction] = $this->trend($current, $previous);

        $icon = match ($direction) {
            'up' => 'heroicon-m-arrow-trending-up',
            'down' => 'heroicon-m-arrow-trending-down',
            default => 'heroicon-m-minus',
        };

        $isGood = match ($goodDirection) {
            'down' => $direction === 'down',
            'flat' => $direction === 'flat',
            default => $direction === 'up',
        };

        $color = $direction === 'flat'
            ? 'gray'
            : ($isGood ? 'success' : 'danger');

        $label = $direction === 'flat'
            ? 'No change vs previous period'
            : (($direction === 'up' ? 'Up ' : 'Down ') . $percent . '% vs previous period');

        return [$label, $icon, $color];
    }

    protected function sparklineForOrders(Carbon $from, Carbon $to, string $column): array
    {
        $days = max(1, $from->diffInDays($to) + 1);
        $points = [];

        $segments = 7;
        $segmentDays = max(1, (int) ceil($days / $segments));

        $cursor = $from->copy();

        for ($i = 0; $i < $segments; $i++) {
            $start = $cursor->copy();
            $end = $cursor->copy()->addDays($segmentDays - 1)->min($to);

            $points[] = round((float) PurchaseOrder::query()
                ->whereNull('deleted_at')
                ->whereBetween('order_date', [$start->toDateString(), $end->toDateString()])
                ->sum($column), 2);

            $cursor = $end->copy()->addDay();

            if ($cursor->gt($to)) {
                while (count($points) < $segments) {
                    $points[] = 0;
                }

                break;
            }
        }

        return $points;
    }

    protected function sparklineForPayments(Carbon $from, Carbon $to): array
    {
        $days = max(1, $from->diffInDays($to) + 1);
        $points = [];

        $segments = 7;
        $segmentDays = max(1, (int) ceil($days / $segments));

        $cursor = $from->copy();

        for ($i = 0; $i < $segments; $i++) {
            $start = $cursor->copy();
            $end = $cursor->copy()->addDays($segmentDays - 1)->min($to);

            $points[] = round((float) PurchaseOrderPayment::query()
                ->whereNull('deleted_at')
                ->where('status', 'successful')
                ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
                ->sum('amount'), 2);

            $cursor = $end->copy()->addDay();

            if ($cursor->gt($to)) {
                while (count($points) < $segments) {
                    $points[] = 0;
                }

                break;
            }
        }

        return $points;
    }

    protected function getStats(): array
    {
        [$from, $to] = $this->range();
        [$previousFrom, $previousTo] = $this->previousRange($from, $to);

        $procurementValue = (float) PurchaseOrder::query()
            ->whereNull('deleted_at')
            ->whereBetween('order_date', [$from->toDateString(), $to->toDateString()])
            ->sum('grand_total');

        $previousProcurementValue = (float) PurchaseOrder::query()
            ->whereNull('deleted_at')
            ->whereBetween('order_date', [$previousFrom->toDateString(), $previousTo->toDateString()])
            ->sum('grand_total');

        $outstanding = (float) PurchaseOrder::query()
            ->whereNull('deleted_at')
            ->whereBetween('order_date', [$from->toDateString(), $to->toDateString()])
            ->where('balance_due', '>', 0)
            ->sum('balance_due');

        $previousOutstanding = (float) PurchaseOrder::query()
            ->whereNull('deleted_at')
            ->whereBetween('order_date', [$previousFrom->toDateString(), $previousTo->toDateString()])
            ->where('balance_due', '>', 0)
            ->sum('balance_due');

        $paidInRange = (float) PurchaseOrderPayment::query()
            ->whereNull('deleted_at')
            ->where('status', 'successful')
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $previousPaid = (float) PurchaseOrderPayment::query()
            ->whereNull('deleted_at')
            ->where('status', 'successful')
            ->whereBetween('payment_date', [$previousFrom->toDateString(), $previousTo->toDateString()])
            ->sum('amount');

        $overdueInvoices = PurchaseOrder::query()
            ->whereNull('deleted_at')
            ->where('balance_due', '>', 0)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->whereDate('due_date', '<', now('Africa/Nairobi')->toDateString())
            ->count();

        $lowStockCount = InventoryItem::query()
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->get()
            ->filter(fn (InventoryItem $item) => $item->is_low_stock)
            ->count();

        [$procurementTrend, $procurementIcon, $procurementColor] = $this->trendText($procurementValue, $previousProcurementValue, 'down');
        [$outstandingTrend, $outstandingIcon, $outstandingColor] = $this->trendText($outstanding, $previousOutstanding, 'down');
        [$paidTrend, $paidIcon, $paidColor] = $this->trendText($paidInRange, $previousPaid, 'up');

        return [
            Stat::make('Procurement', 'KES ' . number_format($procurementValue, 2))
                ->description($procurementTrend)
                ->descriptionIcon($procurementIcon)
                ->color($procurementColor)
                ->icon('heroicon-o-clipboard-document-list')
                ->chart($this->sparklineForOrders($from, $to, 'grand_total'))
                ->url(PurchaseOrderResource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'procurement-stat-card',
                ]),

            Stat::make('Payables', 'KES ' . number_format($outstanding, 2))
                ->description($outstandingTrend)
                ->descriptionIcon($outstandingIcon)
                ->color($outstanding > 0 ? $outstandingColor : 'success')
                ->icon('heroicon-o-banknotes')
                ->chart($this->sparklineForOrders($from, $to, 'balance_due'))
                ->url(PurchaseOrderResource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'procurement-stat-card',
                ]),

            Stat::make('Paid', 'KES ' . number_format($paidInRange, 2))
                ->description($paidTrend)
                ->descriptionIcon($paidIcon)
                ->color($paidColor)
                ->icon('heroicon-o-wallet')
                ->chart($this->sparklineForPayments($from, $to))
                ->url(PurchaseOrderPaymentResource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'procurement-stat-card',
                ]),

            Stat::make('Alerts', $overdueInvoices . ' overdue • ' . $lowStockCount . ' low')
                ->description(($overdueInvoices + $lowStockCount) > 0 ? 'Needs procurement attention' : 'No critical exception')
                ->descriptionIcon(($overdueInvoices + $lowStockCount) > 0 ? 'heroicon-o-bell-alert' : 'heroicon-o-check-circle')
                ->color(($overdueInvoices + $lowStockCount) > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-bell-alert')
                ->chart([$overdueInvoices, $lowStockCount, $overdueInvoices + $lowStockCount, $lowStockCount, $overdueInvoices, 0, $overdueInvoices + $lowStockCount])
                ->url(InventoryItemResource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'procurement-stat-card',
                ]),
        ];
    }
}
