<?php

namespace App\Filament\Widgets\HR;

use App\Models\HR\Employee;
use App\Models\HR\Payroll;
use App\Models\HR\PayrollItem;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use App\Filament\Resources\HR\EmployeeResource;
use App\Filament\Resources\HR\PayrollResource;
use App\Filament\Resources\HR\SalaryAdvanceResource;
class HrAdvancedStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    public static function canView(): bool
{
    return auth()->user()?->can('view hr dashboard') ?? false;
}

    protected function getStats(): array
    {
        $employeesCount = Employee::query()->count();

        $currentPayroll = Payroll::query()
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->latest('id')
            ->first();

        $grossPay = 0.00;
        $allowances = 0.00;
        $nssf = 0.00;
        $sha = 0.00;
        $housingLevy = 0.00;
        $paye = 0.00;
        $nita = 0.00;

        if ($currentPayroll) {
            $items = PayrollItem::query()
                ->where('payroll_id', $currentPayroll->id);

            $grossPay = (float) (clone $items)->sum('gross_pay');
            $allowances = (float) (clone $items)->sum('allowances_total');
            $nssf = (float) (clone $items)->sum('nssf');
            $sha = (float) (clone $items)->sum('sha');
            $housingLevy = (float) (clone $items)->sum('housing_levy');
            $paye = (float) (clone $items)->sum('paye');

            if (Schema::hasColumn('payroll_items', 'nita')) {
                $nita = (float) (clone $items)->sum('nita');
            }
        }

        return [
            Stat::make('Staff', number_format($employeesCount))
        ->icon('heroicon-o-users')
        ->description('Total employees in HR')
        ->descriptionColor('gray')
        ->iconColor('primary')
        ->backgroundColor('gray')
        ->iconBackgroundColor('primary')
        ->iconPosition('end')
        ->url(auth()->user()?->can('view employees') ? EmployeeResource::getUrl('index') : null),

    Stat::make('Salaries', 'KES ' . number_format($grossPay, 2))
        ->icon('heroicon-o-banknotes')
        ->description('Current month gross payroll')
        ->descriptionColor('gray')
        ->iconColor('success')
        ->backgroundColor('gray')
        ->iconBackgroundColor('success')
        ->iconPosition('end')
        ->url(auth()->user()?->can('view payroll') ? PayrollResource::getUrl('index') : null),

    Stat::make('Allowances', 'KES ' . number_format($allowances, 2))
        ->icon('heroicon-o-hand-raised')
        ->description('Current month allowances')
        ->descriptionColor('gray')
        ->iconColor('warning')
        ->backgroundColor('gray')
        ->iconBackgroundColor('warning')
        ->iconPosition('end')
        ->url(PayrollResource::getUrl('index')),

            Stat::make('NSSF', 'KES ' . number_format($nssf, 2))
                ->icon('heroicon-o-shield-check')
                ->description('NSSF payable this month')
                ->descriptionColor('gray')
                ->iconColor('info')
                ->backgroundColor('gray')
                ->iconBackgroundColor('info')
                ->iconPosition('end'),

            Stat::make('SHA', 'KES ' . number_format($sha, 2))
                ->icon('heroicon-o-heart')
                ->description('SHA payable this month')
                ->descriptionColor('gray')
                ->iconColor('primary')
                ->backgroundColor('gray')
                ->iconBackgroundColor('primary')
                ->iconPosition('end'),

            Stat::make('Housing Levy', 'KES ' . number_format($housingLevy, 2))
                ->icon('heroicon-o-home-modern')
                ->description('Housing levy payable')
                ->descriptionColor('gray')
                ->iconColor('warning')
                ->backgroundColor('gray')
                ->iconBackgroundColor('warning')
                ->iconPosition('end'),

            Stat::make('PAYE', 'KES ' . number_format($paye, 2))
                ->icon('heroicon-o-building-library')
                ->description('PAYE payable this month')
                ->descriptionColor('gray')
                ->iconColor('danger')
                ->backgroundColor('gray')
                ->iconBackgroundColor('danger')
                ->iconPosition('end'),

            Stat::make('NITA Levy', 'KES ' . number_format($nita, 2))
                ->icon('heroicon-o-academic-cap')
                ->description('NITA payable this month')
                ->descriptionColor('gray')
                ->iconColor('success')
                ->backgroundColor('gray')
                ->iconBackgroundColor('success')
                ->iconPosition('end'),
        ];
    }
}
