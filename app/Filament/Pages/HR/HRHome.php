<?php

namespace App\Filament\Pages\HR;

use App\Filament\Resources\HR\EmployeeResource;
use App\Filament\Resources\HR\LeaveApplicationResource;
use App\Filament\Resources\HR\PayrollResource;
use App\Filament\Resources\HR\SalaryAdvanceResource;
use App\Models\HR\Employee;
use App\Models\HR\LeaveApplication;
use App\Models\HR\Payroll;
use App\Models\HR\PayrollItem;
use App\Models\HR\SalaryAdvance;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class HRHome extends Page
{
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'Human Resource';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.hr.hr-home';

    protected ?string $heading = '';

    /**
     * The custom Blade view provides its own dashboard header.
     */
    public function getHeader(): ?View
    {
        return null;
    }

    /**
     * Keep this empty.
     *
     * HrAdvancedStatsWidget previously produced the duplicate cards shown above
     * the custom HR dashboard.
     */
    public function getHeaderWidgets(): array
    {
        return [];
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
        /*
        |--------------------------------------------------------------------------
        | Employee counts
        |--------------------------------------------------------------------------
        |
        | employeeCount contains all employee records.
        | activeEmployeeCount contains only employees whose current status is
        | active. NITA is calculated from activeEmployeeCount only.
        */
        $employeeCount = Employee::query()->count();

        $activeEmployeeQuery = Employee::query();

        if (Schema::hasColumn('employees', 'employment_status')) {
            $activeEmployeeQuery->whereRaw(
                'LOWER(TRIM(employment_status)) IN (?, ?)',
                [
                    'active',
                    'currently active',
                ]
            );
        } elseif (Schema::hasColumn('employees', 'status')) {
            $activeEmployeeQuery->whereRaw(
                'LOWER(TRIM(status)) IN (?, ?)',
                [
                    'active',
                    'currently active',
                ]
            );
        } elseif (Schema::hasColumn('employees', 'is_active')) {
            $activeEmployeeQuery->where('is_active', true);
        }

        /*
         * When no employment-status column exists, the query remains unfiltered.
         * In that legacy structure all existing employee records are treated as
         * active because the database provides no status field.
         */
        $activeEmployeeCount = $activeEmployeeQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Pending leave applications
        |--------------------------------------------------------------------------
        */
        $pendingLeaves = LeaveApplication::query()
            ->with([
                'employee',
                'leaveType',
            ])
            ->where('approval_status', 'pending')
            ->latest('id')
            ->limit(6)
            ->get();

        $pendingLeavesCount = LeaveApplication::query()
            ->where('approval_status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Pending salary advances
        |--------------------------------------------------------------------------
        */
        $pendingAdvances = SalaryAdvance::query()
            ->with('employee')
            ->where('approval_status', 'pending')
            ->latest('id')
            ->limit(6)
            ->get();

        $pendingAdvancesCount = SalaryAdvance::query()
            ->where('approval_status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Payroll period
        |--------------------------------------------------------------------------
        |
        | Prefer the current month payroll. When one has not been generated,
        | display the latest payroll available in the system.
        */
        $currentPayroll = Payroll::query()
            ->where('month', now('Africa/Nairobi')->month)
            ->where('year', now('Africa/Nairobi')->year)
            ->latest('id')
            ->first();

        if (! $currentPayroll) {
            $currentPayroll = Payroll::query()
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->orderByDesc('id')
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | Payroll items
        |--------------------------------------------------------------------------
        |
        | Payroll monetary figures come only from payroll_items linked to the
        | selected payroll. Employee salary-profile values are never substituted
        | for a payroll that has not been generated.
        */
        $payrollItems = new Collection();

        if ($currentPayroll) {
            $payrollItems = PayrollItem::query()
                ->where('payroll_id', $currentPayroll->getKey())
                ->get();
        }

        $salaryPayable = $this->sumPayrollColumn(
            $payrollItems,
            'net_pay'
        );

        $grossPay = $this->sumPayrollColumn(
            $payrollItems,
            'gross_pay'
        );

        $payeTotal = $this->sumPayrollColumn(
            $payrollItems,
            'paye'
        );

        $nssfTotal = $this->sumPayrollColumn(
            $payrollItems,
            'nssf'
        );

        $shaTotal = $this->sumPayrollColumn(
            $payrollItems,
            'sha'
        );

        $housingLevyTotal = $this->sumPayrollColumn(
            $payrollItems,
            'housing_levy'
        );

        /*
        |--------------------------------------------------------------------------
        | NITA Levy
        |--------------------------------------------------------------------------
        |
        | NITA payable is KES 50 multiplied by the number of active employees.
        | Exited, inactive, suspended, resigned, dismissed, and terminated staff
        | are excluded by the active-employee query above.
        */
        $nitaRatePerActiveEmployee = 50.00;

        $nitaTotal =
            $activeEmployeeCount * $nitaRatePerActiveEmployee;

        /*
        |--------------------------------------------------------------------------
        | Internal ERP URLs
        |--------------------------------------------------------------------------
        */
        $employeeUrl = EmployeeResource::getUrl('index');

        $leaveApplicationsUrl =
            LeaveApplicationResource::getUrl('index');

        $salaryAdvancesUrl =
            SalaryAdvanceResource::getUrl('index');

        $payrollUrl = PayrollResource::getUrl('index');

        /*
        |--------------------------------------------------------------------------
        | Quick HR links
        |--------------------------------------------------------------------------
        */
        $quickLinks = [
            [
                'title' => 'Employees',
                'description' =>
                    'View staff records, departments, contracts, and employment details.',
                'url' => $employeeUrl,
                'icon' => 'heroicon-o-users',
                'gradient' =>
                    'from-emerald-500/90 via-teal-500/90 to-cyan-500/90',
            ],
            [
                'title' => 'Leave Applications',
                'description' =>
                    'Review leave requests, approvals, balances, and scheduling.',
                'url' => $leaveApplicationsUrl,
                'icon' => 'heroicon-o-calendar-days',
                'gradient' =>
                    'from-sky-500/90 via-blue-500/90 to-indigo-500/90',
            ],
            [
                'title' => 'Salary Advances',
                'description' =>
                    'Track requests, approvals, settlements, and outstanding balances.',
                'url' => $salaryAdvancesUrl,
                'icon' => 'heroicon-o-banknotes',
                'gradient' =>
                    'from-amber-500/90 via-orange-500/90 to-rose-500/90',
            ],
            [
                'title' => 'Payroll',
                'description' =>
                    'Access payroll runs, deductions, summaries, and employee pay records.',
                'url' => $payrollUrl,
                'icon' => 'heroicon-o-calculator',
                'gradient' =>
                    'from-violet-500/90 via-fuchsia-500/90 to-pink-500/90',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Internal and external portal configuration
        |--------------------------------------------------------------------------
        */
        $portalMap = [
            'kra' => [
                'label' => 'KRA iTax Employers',
                'short' => 'KRA',
                'url' => 'https://itax.kra.go.ke/KRA-Portal/',
                'description' =>
                    'Access employer tax services, PAYE returns, tax statements, and compliance actions.',
                'use_case' =>
                    'Use this portal for PAYE filing, Housing Levy obligations, and employer tax submissions.',
                'logo' =>
                    '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-lg">KRA</div>',
                'external' => true,
            ],

            'sha' => [
                'label' => 'SHA Employer Portal',
                'short' => 'SHA',
                'url' => 'https://employers.sha.go.ke/members',
                'description' =>
                    'Manage employer health-insurance obligations and staff submissions.',
                'use_case' =>
                    'Use this portal for SHA employer remittances and employee health deductions.',
                'logo' =>
                    '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-lg">SHA</div>',
                'external' => true,
            ],

            'nssf' => [
                'label' => 'NSSF Employer Services',
                'short' => 'NSSF',
                'url' => 'https://eservice.nssfkenya.co.ke/',
                'description' =>
                    'Handle social-security submissions, employer records, and staff contributions.',
                'use_case' =>
                    'Use this portal for NSSF remittances, contribution review, and employer management.',
                'logo' =>
                    '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-sm">NSSF</div>',
                'external' => true,
            ],

            'nita' => [
                'label' => 'NITA Portal',
                'short' => 'NITA',
                'url' => 'https://www.nita.go.ke/',
                'description' =>
                    'Access industrial-training levy resources and employer compliance information.',
                'use_case' =>
                    'Use this portal for NITA levy and employer training-compliance processes.',
                'logo' =>
                    '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-sm">NITA</div>',
                'external' => true,
            ],

            'salary' => [
                'label' => 'Payroll Summary',
                'short' => 'NET PAY',
                'url' => $payrollUrl,
                'description' =>
                    'Open the payroll area to review net salary payable and employee payroll details.',
                'use_case' =>
                    'Use this internal section to review salary payable and payroll processing.',
                'logo' =>
                    '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-sm">PAY</div>',
                'external' => false,
            ],

            'gross' => [
                'label' => 'Gross Payroll Summary',
                'short' => 'GROSS',
                'url' => $payrollUrl,
                'description' =>
                    'Review gross payroll amounts contributing to deductions and employee payroll totals.',
                'use_case' =>
                    'Use this internal section to review gross payroll figures.',
                'logo' =>
                    '<div class="flex h-full w-full items-center justify-center rounded-xl bg-white/10 text-white font-black text-sm">GRS</div>',
                'external' => false,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Payroll and statutory cards
        |--------------------------------------------------------------------------
        */
        $statutoryCards = [
            [
                'label' => 'Salary Payable',
                'amount' => $salaryPayable,
                'icon' => 'heroicon-o-wallet',
                'portal_key' => 'salary',
                'gradient' =>
                    'from-emerald-500/90 via-teal-500/90 to-cyan-500/90',
            ],
            [
                'label' => 'Gross Payroll',
                'amount' => $grossPay,
                'icon' => 'heroicon-o-scale',
                'portal_key' => 'gross',
                'gradient' =>
                    'from-slate-700/90 via-slate-600/90 to-slate-500/90',
            ],
            [
                'label' => 'PAYE',
                'amount' => $payeTotal,
                'icon' => 'heroicon-o-receipt-percent',
                'portal_key' => 'kra',
                'gradient' =>
                    'from-sky-500/90 via-blue-500/90 to-indigo-500/90',
            ],
            [
                'label' => 'NSSF',
                'amount' => $nssfTotal,
                'icon' => 'heroicon-o-shield-check',
                'portal_key' => 'nssf',
                'gradient' =>
                    'from-violet-500/90 via-fuchsia-500/90 to-purple-500/90',
            ],
            [
                'label' => 'SHA',
                'amount' => $shaTotal,
                'icon' => 'heroicon-o-heart',
                'portal_key' => 'sha',
                'gradient' =>
                    'from-rose-500/90 via-pink-500/90 to-red-500/90',
            ],
            [
                'label' => 'Housing Levy',
                'amount' => $housingLevyTotal,
                'icon' => 'heroicon-o-home-modern',
                'portal_key' => 'kra',
                'gradient' =>
                    'from-amber-500/90 via-orange-500/90 to-red-500/90',
            ],
            [
                'label' => 'NITA Levy',
                'amount' => $nitaTotal,
                'icon' => 'heroicon-o-academic-cap',
                'portal_key' => 'nita',
                'gradient' =>
                    'from-teal-500/90 via-emerald-500/90 to-lime-500/90',
                'calculation' =>
                    number_format($activeEmployeeCount)
                    . ' active employee(s) × KES '
                    . number_format($nitaRatePerActiveEmployee, 2),
            ],
        ];

        return [
            'employeeCount' => $employeeCount,
            'activeEmployeeCount' => $activeEmployeeCount,

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

            'nitaRatePerActiveEmployee' =>
                $nitaRatePerActiveEmployee,

            'nitaTotal' => $nitaTotal,

            'quickLinks' => $quickLinks,
            'statutoryCards' => $statutoryCards,
            'portalMap' => $portalMap,

            'leaveApplicationsUrl' => $leaveApplicationsUrl,
            'salaryAdvancesUrl' => $salaryAdvancesUrl,
            'payrollUrl' => $payrollUrl,
        ];
    }

    /**
     * Safely total a payroll-item column for the selected payroll.
     */
    private function sumPayrollColumn(
        Collection $payrollItems,
        string $column
    ): float {
        return (float) $payrollItems->sum(
            fn (PayrollItem $item): float =>
                (float) ($item->{$column} ?? 0)
        );
    }
}
