<?php

use App\Jobs\ReconcilePayment;
use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('carverse:reconcile-payments', function () {
    if (config('payments.driver') !== 'daraja') {
        return;
    }PaymentAttempt::where('provider', 'daraja')->where('status', '!=', 'paid')->whereNotNull('callback_amount_minor')->where('created_at', '>', now()->subDays(2))->chunkById(100, function ($rows) {
        foreach ($rows as $p) {
            ReconcilePayment::dispatch($p->id, $p->callback_amount_minor);
        }
    });
});
Schedule::command('carverse:reconcile-payments')->everyFiveMinutes()->withoutOverlapping();
