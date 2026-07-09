<?php

namespace App\Providers;

use App\Models\Animal;
use App\Models\AnimalWeight;
use App\Models\HR\Employee;
use App\Models\HR\LeaveApplication;
use App\Models\HR\Payroll;
use App\Models\HR\SalaryAdvance;
use App\Models\Sales\Customer;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesPayment;
use App\Observers\AnimalObserver;
use App\Observers\AnimalWeightObserver;
use App\Observers\CustomerObserver;
use App\Observers\EmployeeObserver;
use App\Observers\LeaveApplicationObserver;
use App\Observers\PayrollObserver;
use App\Observers\SalaryAdvanceObserver;
use App\Observers\SalesInvoiceObserver;
use App\Observers\SalesPaymentObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Animal::observe(AnimalObserver::class);
        AnimalWeight::observe(AnimalWeightObserver::class);

        Employee::observe(EmployeeObserver::class);
        LeaveApplication::observe(LeaveApplicationObserver::class);
        Payroll::observe(PayrollObserver::class);
        SalaryAdvance::observe(SalaryAdvanceObserver::class);

        Customer::observe(CustomerObserver::class);
        SalesInvoice::observe(SalesInvoiceObserver::class);
        SalesPayment::observe(SalesPaymentObserver::class);
    }
}
