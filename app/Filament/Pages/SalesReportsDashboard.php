<?php

namespace App\Filament\Pages;

use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesPayment;
use App\Models\DashboardWidgetPreference;
use App\Support\Dashboards\SalesDashboardWidgets;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions;
use Filament\Forms;

class SalesReportsDashboard extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = ' Reports';
    protected static ?string $title = 'Sales Reports Dashboard';
    protected static ?int $navigationSort = 7;
    protected static string $view = 'filament.pages.sales-reports-dashboard';

    public ?array $filters = [];
    public ?array $customizer = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view sales invoices') ||
            auth()->user()?->can('view sales payments') ||
            auth()->user()?->hasRole('Administrator');
    }

    public function mount(): void
    {
        $this->filters = [
            // 'date_from' => now('Africa/Nairobi')->startOfMonth()->toDateString(),
            'date_from' => now('Africa/Nairobi')->startOfYear()->toDateString(),
            'date_to' => now('Africa/Nairobi')->toDateString(),
            'payment_status' => null,
            'invoice_status' => null,
            'payment_method' => null,
        ];

        $this->loadCustomizerState();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Filters')
                    ->description('Filter sales reports by date, payment status, payment method, and invoice status.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 5,
                    ])
                    ->schema([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From Date')
                            ->live(),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To Date')
                            ->live(),
                        Forms\Components\Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'unpaid' => 'Unpaid',
                                'partial' => 'Partial',
                                'paid' => 'Paid',
                                'overpaid' => 'Overpaid',
                            ])
                            ->placeholder('All')
                            ->live(),
                        Forms\Components\Select::make('invoice_status')
                            ->label('Invoice Status')
                            ->options([
                                'draft' => 'Draft',
                                'issued' => 'Issued',
                                'approved' => 'Approved',
                                'cancelled' => 'Cancelled',
                            ])
                            ->placeholder('All')
                            ->live(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'mpesa_stk' => 'M-Pesa STK',
                                'mpesa_paybill' => 'M-Pesa Paybill',
                                'bank_transfer' => 'Bank Transfer',
                                'cash' => 'Cash',
                                'cheque' => 'Cheque',
                                'other' => 'Other',
                            ])
                            ->placeholder('All')
                            ->live(),
                    ]),
            ])
            ->statePath('filters');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printExecutiveReport')
                ->label('Print Report')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(function () {
                    ini_set('max_execution_time', 300);
                    ini_set('memory_limit', '1024M');
                    set_time_limit(300);

                    $dateFrom = \Carbon\Carbon::parse($this->filters['date_from'])->toDateString();
                    $dateTo = \Carbon\Carbon::parse($this->filters['date_to'])->toDateString();

                    $invoices = \App\Models\Sales\SalesInvoice::query()
                        ->with(['customer', 'items.animal'])
                        ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                        ->when($this->filters['payment_status'] ?? null, fn($q, $status) => $q->where('payment_status', $status))
                        ->when($this->filters['invoice_status'] ?? null, fn($q, $status) => $q->where('status', $status))
                        ->when($this->filters['sale_type'] ?? null, fn($q, $saleType) => $q->where('sale_type', $saleType))
                        ->orderByDesc('invoice_date')
                        ->get();

                    $payments = \App\Models\Sales\SalesPayment::query()
                        ->with(['invoice.customer', 'customer', 'receivedBy', 'verifiedBy'])
                        ->whereBetween('payment_date', [$dateFrom, $dateTo])
                        ->when($this->filters['payment_method'] ?? null, fn($q, $method) => $q->where('payment_method', $method))
                        ->when($this->filters['payment_status'] ?? null, function ($q, $status) {
                            $q->whereHas('invoice', fn($invoiceQuery) => $invoiceQuery->where('payment_status', $status));
                        })
                        ->when($this->filters['invoice_status'] ?? null, function ($q, $status) {
                            $q->whereHas('invoice', fn($invoiceQuery) => $invoiceQuery->where('status', $status));
                        })
                        ->orderByDesc('payment_date')
                        ->get();

                    $successfulPayments = $payments->where('status', 'successful');

                    $totalSales = (float) $invoices->sum('grand_total');
                    $amountPaid = (float) $successfulPayments->sum('amount');
                    $balanceDue = max(0, $totalSales - $amountPaid);

                    $saleTypeSummary = $invoices
                        ->groupBy('sale_type')
                        ->map(fn($items, $type) => [
                            'type' => str($type ?: 'general')->replace('_', ' ')->title(),
                            'count' => $items->count(),
                            'total' => (float) $items->sum('grand_total'),
                            'paid' => (float) $items->sum('amount_paid'),
                            'balance' => (float) $items->sum('balance_due'),
                        ])
                        ->sortByDesc('total')
                        ->values();

                    $paymentMethodSummary = $successfulPayments
                        ->groupBy('payment_method')
                        ->map(fn($items, $method) => [
                            'method' => str($method ?: 'unknown')->replace('_', ' ')->title(),
                            'count' => $items->count(),
                            'total' => (float) $items->sum('amount'),
                        ])
                        ->sortByDesc('total')
                        ->values();

                    $topCustomers = $invoices
                        ->groupBy('customer_id')
                        ->map(function ($items) {
                            $customer = $items->first()->customer;

                            return [
                                'name' => $customer?->name ?? 'Unknown Customer',
                                'invoice_count' => $items->count(),
                                'total' => (float) $items->sum('grand_total'),
                                'paid' => (float) $items->sum('amount_paid'),
                                'balance' => (float) $items->sum('balance_due'),
                            ];
                        })
                        ->sortByDesc('total')
                        ->take(10)
                        ->values();

                    $collectionRate = $totalSales > 0
                        ? round(($amountPaid / $totalSales) * 100, 1)
                        : 0;

                    $smartNotes = [
                        "The report covers sales activity from {$dateFrom} to {$dateTo}.",
                        'Total invoice value is KES ' . number_format($totalSales, 2) . '.',
                        'Confirmed collections are KES ' . number_format($amountPaid, 2) . '.',
                        'Outstanding balances are KES ' . number_format($balanceDue, 2) . '.',
                    ];

                    if ($saleTypeSummary->isNotEmpty()) {
                        $topSaleType = $saleTypeSummary->first();

                        $smartNotes[] = "{$topSaleType['type']} is currently the strongest sale type by value.";
                    }

                    if ($paymentMethodSummary->isNotEmpty()) {
                        $topMethod = $paymentMethodSummary->first();

                        $smartNotes[] = "{$topMethod['method']} is the leading successful payment method.";
                    }

                    $suggestions = [];

                    if ($balanceDue > 0) {
                        $suggestions[] = 'Follow up on unpaid and partially paid invoices.';
                    }

                    if ($payments->where('status', 'pending')->count() > 0) {
                        $suggestions[] = 'Review pending payments and verify M-Pesa transaction codes where applicable.';
                    }

                    if ($saleTypeSummary->count() > 1) {
                        $suggestions[] = 'Compare sale type performance to identify the strongest revenue streams.';
                    }

                    if ($paymentMethodSummary->where('method', 'Cash')->sum('total') > 0) {
                        $suggestions[] = 'Encourage digital payment channels for better reconciliation and audit trails.';
                    }

                    if (empty($suggestions)) {
                        $suggestions[] = 'Sales and collections appear stable for the selected period.';
                    }

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.sales.sales-dashboard-report', [
                        'dateFrom' => $dateFrom,
                        'dateTo' => $dateTo,
                        'filters' => $this->filters,
                        'invoices' => $invoices,
                        'payments' => $payments,
                        'saleTypeSummary' => $saleTypeSummary,
                        'paymentMethodSummary' => $paymentMethodSummary,
                        'topCustomers' => $topCustomers,
                        'smartNotes' => $smartNotes,
                        'suggestions' => $suggestions,
                        'totalSales' => $totalSales,
                        'amountPaid' => $amountPaid,
                        'balanceDue' => $balanceDue,
                        'successfulPayments' => (float) $successfulPayments->sum('amount'),
                        'invoiceCount' => $invoices->count(),
                        'paymentCount' => $payments->count(),
                        'collectionRate' => $collectionRate,
                        'maxSaleType' => max(1, (float) $saleTypeSummary->max('total')),
                        'maxPaymentMethod' => max(1, (float) $paymentMethodSummary->max('total')),
                        'generatedBy' => auth()->user(),
                        'generatedByRole' => auth()->user()?->getRoleNames()?->first() ?? 'User',
                    ])
                        ->setPaper('a4', 'landscape')
                        ->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                            'dpi' => 96,
                            'defaultFont' => 'Courier',
                        ]);

                    return response()->streamDownload(
                        fn() => print ($pdf->output()),
                        'sales-dashboard-report-' . now('Africa/Nairobi')->format('Ymd_His') . '.pdf'
                    );
                }),
            Actions\Action::make('customizeDashboard')
                ->label('Customize')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('gray')
                ->slideOver()
                ->modalWidth('lg')
                ->modalHeading('Customize Sales Dashboard')
                ->modalDescription('Choose the widgets you want to display and arrange their order.')
                ->form([
                    Forms\Components\Repeater::make('widgets')
                        ->label('Dashboard Widgets')
                        ->reorderable()
                        ->schema([
                            Forms\Components\Hidden::make('widget_key'),
                            Forms\Components\TextInput::make('label')
                                ->label('Widget')
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\Toggle::make('is_visible')
                                ->label('Show')
                                ->inline(false),
                        ])
                        ->columns(2)
                        ->itemLabel(fn(array $state): ?string => $state['label'] ?? null)
                        ->columnSpanFull(),
                ])
                ->fillForm(fn() => $this->customizer)
                ->action(function (array $data): void {
                    foreach (($data['widgets'] ?? []) as $index => $widget) {
                        DashboardWidgetPreference::query()->updateOrCreate(
                            [
                                'user_id' => auth()->id(),
                                'dashboard_key' => 'sales_reports',
                                'widget_key' => $widget['widget_key'],
                            ],
                            [
                                'is_visible' => (bool) ($widget['is_visible'] ?? false),
                                'sort_order' => $index + 1,
                            ]
                        );
                    }

                    $this->loadCustomizerState();

                    Notification::make()
                        ->title('Dashboard updated')
                        ->body('Your dashboard layout has been saved.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function loadCustomizerState(): void
    {
        $widgets = SalesDashboardWidgets::all();

        $items = [];

        foreach ($widgets as $key => $widget) {
            $preference = DashboardWidgetPreference::query()
                ->where('user_id', auth()->id())
                ->where('dashboard_key', 'sales_reports')
                ->where('widget_key', $key)
                ->first();

            $items[] = [
                'widget_key' => $key,
                'label' => $widget['label'],
                'is_visible' => $preference?->is_visible ?? $widget['default_visible'],
                'sort_order' => $preference?->sort_order ?? $widget['sort_order'],
            ];
        }

        $this->customizer = [
            'widgets' => collect($items)->sortBy('sort_order')->values()->toArray(),
        ];
    }

    public function getWidgets(): array
    {
        $registry = SalesDashboardWidgets::all();

        $preferences = DashboardWidgetPreference::query()
            ->where('user_id', auth()->id())
            ->where('dashboard_key', 'sales_reports')
            ->get()
            ->keyBy('widget_key');

        return collect($registry)
            ->map(function (array $widget, string $key) use ($preferences): array {
                $preference = $preferences->get($key);

                return [
                    'class' => $widget['class'],
                    'is_visible' => $preference?->is_visible ?? $widget['default_visible'],
                    'sort_order' => $preference?->sort_order ?? $widget['sort_order'],
                ];
            })
            ->filter(fn(array $widget): bool => (bool) $widget['is_visible'])
            ->sortBy('sort_order')
            ->pluck('class')
            ->values()
            ->toArray();
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }
}
