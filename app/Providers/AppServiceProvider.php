<?php

namespace App\Providers;

use App\Services\Payments\DarajaPayments;
use App\Services\Payments\DemoPayments;
use App\Services\Payments\PaymentProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentProvider::class, fn () => config('payments.driver') === 'daraja' ? new DarajaPayments : new DemoPayments);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
