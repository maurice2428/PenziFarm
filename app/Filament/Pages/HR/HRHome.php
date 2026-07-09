<?php

namespace App\Filament\Pages\HR;

use App\Filament\Resources\HR\EmployeeResource;
use App\Filament\Resources\HR\LeaveApplicationResource;
use App\Filament\Resources\HR\PayrollResource;
use App\Filament\Resources\HR\SalaryAdvanceResource;
use App\Filament\Widgets\HR\HrAdvancedStatsWidget;
use App\Models\HR\Employee;
use App\Models\HR\LeaveApplication;
use App\Models\HR\Payroll;
use App\Models\HR\PayrollItem;
use App\Models\HR\SalaryAdvance;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;

class HRHome extends Page
{
    // protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationGroup = 'Human Resource';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.hr.hr-home';

    protected ?string $heading = '';

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return null;
    }

    public function getHeaderWidgets(): array
    {
        return [
            HrAdvancedStatsWidget::class,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('view hr dashboard') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view hr dashboard') ?? false;
    }

    protected function getViewData(): array
    {
        $employeeCount = Employee::query()->count();

        $pendingLeaves = LeaveApplication::query()
            ->with(['employee', 'leaveType'])
            ->where('approval_status', 'pending')
            ->latest('id')
            ->limit(6)
            ->get();

        $pendingAdvances = SalaryAdvance::query()
            ->with(['employee'])
            ->where('approval_status', 'pending')
            ->latest('id')
            ->limit(6)
            ->get();

        $pendingLeavesCount = LeaveApplication::query()
            ->where('approval_status', 'pending')
            ->count();

        $pendingAdvancesCount = SalaryAdvance::query()
            ->where('approval_status', 'pending')
            ->count();

        $currentPayroll = Payroll::query()
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->latest('id')
            ->first();

        $salaryPayable = 0.0;
        $grossPay = 0.0;
        $payeTotal = 0.0;
        $nssfTotal = 0.0;
        $shaTotal = 0.0;
        $housingLevyTotal = 0.0;
        $nitaTotal = $employeeCount * 50;

        if ($currentPayroll) {
            $salaryPayable = (float) PayrollItem::query()
                ->where('payroll_id', $currentPayroll->id)
                ->sum('net_pay');

            $grossPay = (float) PayrollItem::query()
                ->where('payroll_id', $currentPayroll->id)
                ->sum('gross_pay');

            $payeTotal = (float) PayrollItem::query()
                ->where('payroll_id', $currentPayroll->id)
                ->sum('paye');

            $nssfTotal = (float) PayrollItem::query()
                ->where('payroll_id', $currentPayroll->id)
                ->sum('nssf');

            $shaTotal = (float) PayrollItem::query()
                ->where('payroll_id', $currentPayroll->id)
                ->sum('sha');

            $housingLevyTotal = (float) PayrollItem::query()
                ->where('payroll_id', $currentPayroll->id)
                ->sum('housing_levy');

            if (Schema::hasColumn('payroll_items', 'nita')) {
                $nitaTotal = (float) PayrollItem::query()
                    ->where('payroll_id', $currentPayroll->id)
                    ->sum('nita');
            }
        }

        $employeeUrl = EmployeeResource::getUrl('index');
        $leaveApplicationsUrl = LeaveApplicationResource::getUrl('index');
        $salaryAdvancesUrl = SalaryAdvanceResource::getUrl('index');
        $payrollUrl = PayrollResource::getUrl('index');

        $quickLinks = [
            [
                'title' => 'Employees',
                'description' => 'View staff records, departments, contracts, and employment details.',
                'url' => $employeeUrl,
                'icon' => 'heroicon-o-users',
                'gradient' => 'from-emerald-500/90 via-teal-500/90 to-cyan-500/90',
            ],
            [
                'title' => 'Leave Applications',
                'description' => 'Review leave requests, approvals, balances, and scheduling.',
                'url' => $leaveApplicationsUrl,
                'icon' => 'heroicon-o-calendar-days',
                'gradient' => 'from-sky-500/90 via-blue-500/90 to-indigo-500/90',
            ],
            [
                'title' => 'Salary Advances',
                'description' => 'Track requests, approvals, settlements, and outstanding balances.',
                'url' => $salaryAdvancesUrl,
                'icon' => 'heroicon-o-banknotes',
                'gradient' => 'from-amber-500/90 via-orange-500/90 to-rose-500/90',
            ],
            [
                'title' => 'Payroll',
                'description' => 'Access payroll runs, deductions, summaries, and employee pay records.',
                'url' => $payrollUrl,
                'icon' => 'heroicon-o-calculator',
                'gradient' => 'from-violet-500/90 via-fuchsia-500/90 to-pink-500/90',
            ],
        ];

        $portalMap = [
            'kra' => [
                'label' => 'KRA iTax (Employers)',
                'short' => 'KRA',
                'url' => 'https://itax.kra.go.ke/KRA-Portal/',
                'description' => 'Access employer tax services, PAYE returns, tax statements, and compliance actions.',
                'use_case' => 'Use this portal for PAYE filing, employer tax obligations, and tax-related payroll submissions.',
                'logo' => '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-lg">KRA</div>',
            ],
            'sha' => [
                'label' => 'SHA Employer Portal',
                'short' => 'SHA',
                'url' => 'https://www.sha.go.ke/',
                'description' => 'Manage employer health insurance obligations and staff-related submissions under SHA.',
                'use_case' => 'Use this portal for SHA-related employer remittances and statutory employee health deductions.',
                'logo' => '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-lg">SHA</div>',
            ],
            'nssf' => [
                'label' => 'NSSF Employer Services',
                'short' => 'NSSF',
                'url' => 'https://www.nssf.or.ke/',
                'description' => 'Handle social security submissions, employer records, and staff contribution compliance.',
                'use_case' => 'Use this portal for NSSF remittances, contribution review, and employer social security management.',
                'logo' => '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-sm">NSSF</div>',
            ],
            'nita' => [
                'label' => 'NITA Portal',
                'short' => 'NITA',
                'url' => 'https://www.nita.go.ke/',
                'description' => 'Access industrial training levy resources and employer-facing training compliance information.',
                'use_case' => 'Use this portal for NITA-related levy and training compliance processes where applicable.',
                'logo' => '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-sm">NITA</div>',
            ],
            'salary' => [
                'label' => 'Payroll Summary',
                'short' => 'NET PAY',
                'url' => $payrollUrl,
                'description' => 'Open your payroll area to review net pay, payroll runs, and salary processing details.',
                'use_case' => 'Use this section for internal payroll review rather than an external government portal.',
                'logo' => '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-sm">PAY</div>',
            ],
            'gross' => [
                'label' => 'Payroll Gross Summary',
                'short' => 'GROSS',
                'url' => $payrollUrl,
                'description' => 'Review the current payroll gross amounts contributing to deductions and employee payroll totals.',
                'use_case' => 'Use this section for internal payroll totals and payroll basis review.',
                'logo' => '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-sm">GRS</div>',
            ],
        ];

        $statutoryCards = [
            [
                'label' => 'Salary Payable',
                'amount' => $salaryPayable,
                'icon' => 'heroicon-o-wallet',
                'portal_key' => 'salary',
                'gradient' => 'from-emerald-500/90 via-teal-500/90 to-cyan-500/90',
            ],
            [
                'label' => 'Gross Payroll',
                'amount' => $grossPay,
                'icon' => 'heroicon-o-scale',
                'portal_key' => 'gross',
                'gradient' => 'from-slate-700/90 via-slate-600/90 to-slate-500/90',
            ],
            [
                'label' => 'PAYE',
                'amount' => $payeTotal,
                'icon' => 'heroicon-o-receipt-percent',
                'portal_key' => 'kra',
                'gradient' => 'from-sky-500/90 via-blue-500/90 to-indigo-500/90',
            ],
            [
                'label' => 'NSSF',
                'amount' => $nssfTotal,
                'icon' => 'heroicon-o-shield-check',
                'portal_key' => 'nssf',
                'gradient' => 'from-violet-500/90 via-fuchsia-500/90 to-purple-500/90',
            ],
            [
                'label' => 'SHA',
                'amount' => $shaTotal,
                'icon' => 'heroicon-o-heart',
                'portal_key' => 'sha',
                'gradient' => 'from-rose-500/90 via-pink-500/90 to-red-500/90',
            ],
            [
                'label' => 'Housing Levy',
                'amount' => $housingLevyTotal,
                'icon' => 'heroicon-o-home-modern',
                'portal_key' => 'kra',
                'gradient' => 'from-amber-500/90 via-orange-500/90 to-red-500/90',
            ],
            [
                'label' => 'NITA',
                'amount' => $nitaTotal,
                'icon' => 'heroicon-o-academic-cap',
                'portal_key' => 'nita',
                'gradient' => 'from-teal-500/90 via-emerald-500/90 to-lime-500/90',
            ],
        ];

        return [
            'employeeCount' => $employeeCount,
            'pendingLeaves' => $pendingLeaves,
            'pendingAdvances' => $pendingAdvances,
            'pendingLeavesCount' => $pendingLeavesCount,
            'pendingAdvancesCount' => $pendingAdvancesCount,
            'currentPayroll' => $currentPayroll,
            'salaryPayable' => $salaryPayable,
            'grossPay' => $grossPay,
            'payeTotal' => $payeTotal,
            'nssfTotal' => $nssfTotal,
            'shaTotal' => $shaTotal,
            'housingLevyTotal' => $housingLevyTotal,
            'nitaTotal' => $nitaTotal,
            'quickLinks' => $quickLinks,
            'statutoryCards' => $statutoryCards,
            'portalMap' => $portalMap,
            'leaveApplicationsUrl' => $leaveApplicationsUrl,
            'salaryAdvancesUrl' => $salaryAdvancesUrl,
        ];
    }
}
