<?php

namespace App\Http\Controllers;

use App\Jobs\ReconcilePayment;
use App\Models\PaymentAttempt;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function __invoke(Request $r, string $token)
    {
        abort_unless(config('payments.driver') === 'daraja' && config('payments.callback_token') && hash_equals(config('payments.callback_token'), $token), 404);
        $d = $r->validate(['Body.stkCallback.CheckoutRequestID' => 'required|string|max:200', 'Body.stkCallback.ResultCode' => 'required|integer', 'Body.stkCallback.CallbackMetadata.Item' => 'nullable|array']);
        $body = $d['Body']['stkCallback'];
        $p = PaymentAttempt::where('provider', 'daraja')->where('provider_reference', $body['CheckoutRequestID'])->first();
        if ($p && $p->status !== 'paid') {
            $items = collect($body['CallbackMetadata']['Item'] ?? [])->pluck('Value', 'Name');
            $amount = (int) round(((float) $items->get('Amount', 0)) * 100);
            if ($amount === $p->amount_minor) {
                $p->update(['callback_amount_minor' => $amount]);
            }ReconcilePayment::dispatch($p->id, $amount);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted for verification']);
    }
}
