<?php

namespace App\Jobs;

use App\Actions\Workflow;
use App\Models\PaymentAttempt;
use App\Services\Payments\DarajaPayments;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ReconcilePayment implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $attemptId, public int $callbackAmount) {}

    public function backoff(): array
    {
        return [30, 60, 180, 600];
    }

    public function handle(DarajaPayments $daraja, Workflow $workflow): void
    {
        $p = PaymentAttempt::findOrFail($this->attemptId);
        if ($p->status === 'paid') {
            return;
        }
        $result = $daraja->query($p);
        if (! array_key_exists('ResultCode', $result)) {
            $this->release(60);

            return;
        }
        if (($result['CheckoutRequestID'] ?? null) !== $p->provider_reference) {
            return;
        }
        if ($p->merchant_reference && ($result['MerchantRequestID'] ?? null) !== $p->merchant_reference) {
            return;
        }
        if ((int) $result['ResultCode'] === 0) {
            if ($this->callbackAmount !== $p->amount_minor) {
                return;
            }$workflow->reconcile($p->reference, $p->provider_reference, $this->callbackAmount, 'KES', true);
        } else {
            DB::transaction(function () use ($p) {
                $locked = PaymentAttempt::lockForUpdate()->findOrFail($p->id);
                if ($locked->status !== 'paid') {
                    $locked->update(['status' => 'failed']);
                }
            });
        }
        // A failure query never downgrades a successful attempt. Late callbacks remain eligible for reconciliation.
    }
}
