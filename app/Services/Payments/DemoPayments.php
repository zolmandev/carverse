<?php

namespace App\Services\Payments;

use App\Actions\Workflow;
use App\Models\PaymentAttempt;
use App\Models\Transaction;
use Illuminate\Support\Str;

class DemoPayments implements PaymentProvider
{
    public function initiate(Transaction $transaction, string $phone): PaymentAttempt
    {
        $attempt = PaymentAttempt::create(['transaction_id' => $transaction->id, 'reference' => (string) Str::uuid(), 'amount_minor' => $transaction->total_minor, 'provider' => 'demo']);
        app(Workflow::class)->reconcile($attempt->reference, 'DEMO-'.$attempt->id, $attempt->amount_minor, 'KES', true);

        return $attempt->refresh();
    }
}
