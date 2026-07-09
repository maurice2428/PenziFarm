<?php

namespace App\Providers;

use App\Console\Commands\AccountingBackfillExistingTransactions;
use App\Listeners\Accounting\AutoPostAccountingFromEloquent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AccountingAutoPostingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen('eloquent.created: *', function (string $eventName, array $payload): void {
            app(AutoPostAccountingFromEloquent::class)->handle($eventName, $payload);
        });

        Event::listen('eloquent.updated: *', function (string $eventName, array $payload): void {
            app(AutoPostAccountingFromEloquent::class)->handle($eventName, $payload);
        });
    }

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AccountingBackfillExistingTransactions::class,
            ]);
        }
    }
}
