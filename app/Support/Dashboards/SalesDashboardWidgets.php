<?php

namespace App\Support\Dashboards;

use App\Filament\Widgets\SalesMonthlyRevenueChart;
use App\Filament\Widgets\SalesPaymentMethodChart;
use App\Filament\Widgets\SalesRecentPaymentsTable;
use App\Filament\Widgets\SalesReportsStats;

class SalesDashboardWidgets
{
    public static function all(): array
    {
        return [
            'sales_reports_stats' => [
                'label' => 'Executive KPI Cards',
                'class' => SalesReportsStats::class,
                'default_visible' => true,
                'sort_order' => 1,
            ],

            'monthly_revenue_chart' => [
                'label' => 'Sales Revenue Trend',
                'class' => SalesMonthlyRevenueChart::class,
                'default_visible' => true,
                'sort_order' => 2,
            ],

            'payment_method_chart' => [
                'label' => 'Payment Method Breakdown',
                'class' => SalesPaymentMethodChart::class,
                'default_visible' => true,
                'sort_order' => 3,
            ],

            'recent_payments_table' => [
                'label' => 'Sales Payments Register',
                'class' => SalesRecentPaymentsTable::class,
                'default_visible' => true,
                'sort_order' => 4,
            ],
        ];
    }
}
