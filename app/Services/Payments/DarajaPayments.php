<?php

namespace App\Services\Payments;

use App\Models\PaymentAttempt;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DarajaPayments implements PaymentProvider
{
    private const BASE = 'https://sandbox.safaricom.co.ke';

    private function token(): string
    {
        foreach (['key', 'secret', 'shortcode', 'passkey', 'callback_token'] as $k) {
            if (! config('payments.'.$k)) {
                throw ValidationException::withMessages(['payment' => 'M-Pesa sandbox configuration is incomplete. Select the demo driver or configure sandbox credentials.']);
            }
        }
        $response = Http::timeout(20)->withBasicAuth(config('payments.key'), config('payments.secret'))->get(self::BASE.'/oauth/v1/generate', ['grant_type' => 'client_credentials']);
        if (! $response->successful() || ! $response->json('access_token')) {
            throw ValidationException::withMessages(['payment' => 'Sandbox authentication unavailable. Please retry later.']);
        }

        return $response->json('access_token');
    }

    private function credentials(): array
    {
        $time = now('Africa/Nairobi')->format('YmdHis');

        return ['BusinessShortCode' => config('payments.shortcode'), 'Password' => base64_encode(config('payments.shortcode').config('payments.passkey').$time), 'Timestamp' => $time];
    }

    public function initiate(Transaction $transaction, string $phone): PaymentAttempt
    {
        abort_unless($transaction->total_minor % 100 === 0, 422, 'Sandbox M-Pesa requires whole KES amounts.');
        $token = $this->token();
        $phone = preg_replace('/^0/', '254', ltrim($phone, '+'));
        $p = PaymentAttempt::create(['transaction_id' => $transaction->id, 'reference' => (string) Str::uuid(), 'amount_minor' => $transaction->total_minor, 'provider' => 'daraja']);
        $response = Http::timeout(25)->withToken($token)->post(self::BASE.'/mpesa/stkpush/v1/processrequest', $this->credentials() + ['TransactionType' => 'CustomerPayBillOnline', 'Amount' => $p->amount_minor / 100, 'PartyA' => $phone, 'PartyB' => config('payments.shortcode'), 'PhoneNumber' => $phone, 'CallBackURL' => url('/payments/callback/'.config('payments.callback_token')), 'AccountReference' => 'CV'.$transaction->id, 'TransactionDesc' => 'Carverse sandbox']);
        if (! $response->successful() || $response->json('ResponseCode') !== '0' || ! $response->json('CheckoutRequestID')) {
            $p->update(['status' => 'failed']);
            throw ValidationException::withMessages(['payment' => 'Sandbox payment could not be initiated. No payment has been confirmed.']);
        }
        $p->update(['provider_reference' => $response->json('CheckoutRequestID'), 'merchant_reference' => $response->json('MerchantRequestID')]);

        return $p->refresh();
    }

    public function query(PaymentAttempt $attempt): array
    {
        $response = Http::timeout(20)->withToken($this->token())->post(self::BASE.'/mpesa/stkpushquery/v1/query', $this->credentials() + ['CheckoutRequestID' => $attempt->provider_reference]);
        if (! $response->successful()) {
            return [];
        }

        return $response->json();
    }
}
