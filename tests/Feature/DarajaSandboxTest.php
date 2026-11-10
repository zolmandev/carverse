<?php

namespace Tests\Feature;

use App\Actions\Workflow;
use App\Models\Organization;
use App\Models\PaymentAttempt;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Payments\DarajaPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DarajaSandboxTest extends TestCase
{
    use RefreshDatabase;

    private Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.driver' => 'daraja', 'payments.key' => 'test-key', 'payments.secret' => 'test-secret', 'payments.shortcode' => '174379', 'payments.passkey' => 'test-passkey', 'payments.callback_token' => 'test-callback-token']);
        $u = User::factory()->create();
        $v = Vehicle::factory()->create(['user_id' => $u->id]);
        $o = Organization::factory()->create();
        $r = ServiceRequest::create(['user_id' => $u->id, 'vehicle_id' => $v->id, 'organization_id' => $o->id, 'kind' => 'service', 'title' => 'Oil', 'notes' => 'Test', 'status' => 'quoted']);
        $q = Quote::create(['service_request_id' => $r->id, 'organization_id' => $o->id, 'items' => [['description' => 'Oil', 'amount_minor' => 150000]], 'total_minor' => 150000, 'valid_until' => today()->addDay(), 'duration' => '1 hour']);
        $this->transaction = app(Workflow::class)->accept($q, $u);
        Http::preventStrayRequests();
    }

    private function fakeQuery(string $checkout = 'checkout-1', string $merchant = 'merchant-1', int $code = 0): void
    {
        Http::fake(['*/oauth/*' => Http::response(['access_token' => 'fake-token']), '*/stkpushquery/*' => Http::response(['CheckoutRequestID' => $checkout, 'MerchantRequestID' => $merchant, 'ResultCode' => $code])]);
    }

    private function attempt(): PaymentAttempt
    {
        return PaymentAttempt::create(['transaction_id' => $this->transaction->id, 'reference' => 'internal-ref', 'provider' => 'daraja', 'provider_reference' => 'checkout-1', 'merchant_reference' => 'merchant-1', 'amount_minor' => 150000]);
    }

    private function callbackPayload(int $amount = 1500): array
    {
        return ['Body' => ['stkCallback' => ['CheckoutRequestID' => 'checkout-1', 'ResultCode' => 0, 'CallbackMetadata' => ['Item' => [['Name' => 'Amount', 'Value' => $amount], ['Name' => 'MpesaReceiptNumber', 'Value' => 'TEST-RECEIPT']]]]]];
    }

    public function test_initiation_records_attempt_and_waits_for_verification(): void
    {
        Http::fake(['*/oauth/*' => Http::response(['access_token' => 'fake-token']), '*/stkpush/v1/*' => Http::response(['ResponseCode' => '0', 'CheckoutRequestID' => 'checkout-1', 'MerchantRequestID' => 'merchant-1'])]);
        $p = app(DarajaPayments::class)->initiate($this->transaction, '0712345678');
        $this->assertSame('pending', $p->status);
        $this->assertSame('unpaid', $this->transaction->fresh()->payment_status);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'processrequest') && (int) $r['Amount'] === 1500 && $r['PhoneNumber'] === '254712345678');
    }

    public function test_callback_requires_provider_query_and_is_idempotent(): void
    {
        $p = $this->attempt();
        $this->fakeQuery();
        $this->postJson('/payments/callback/test-callback-token', $this->callbackPayload())->assertOk();
        $this->postJson('/payments/callback/test-callback-token', $this->callbackPayload())->assertOk();
        $this->assertSame('paid', $p->fresh()->status);
        $this->assertSame('paid', $this->transaction->fresh()->payment_status);
        $this->assertDatabaseCount('audit_events', 2);
    }

    public function test_callback_amount_mismatch_remains_unpaid(): void
    {
        $p = $this->attempt();
        $this->fakeQuery();
        $this->postJson('/payments/callback/test-callback-token', $this->callbackPayload(1))->assertOk();
        $this->assertSame('unpaid', $this->transaction->fresh()->payment_status);
    }

    public function test_mismatched_query_reference_remains_unpaid(): void
    {
        $this->attempt();
        $this->fakeQuery('wrong-checkout');
        $this->postJson('/payments/callback/test-callback-token', $this->callbackPayload())->assertOk();
        $this->assertSame('unpaid', $this->transaction->fresh()->payment_status);
    }

    public function test_failed_provider_query_cannot_trust_success_callback(): void
    {
        $p = $this->attempt();
        Http::fake(['*/oauth/*' => Http::response(['access_token' => 'fake-token']), '*/stkpushquery/*' => Http::sequence()->push(['CheckoutRequestID' => 'checkout-1', 'MerchantRequestID' => 'merchant-1', 'ResultCode' => 1032])->push(['CheckoutRequestID' => 'checkout-1', 'MerchantRequestID' => 'merchant-1', 'ResultCode' => 0])]);
        $this->postJson('/payments/callback/test-callback-token', $this->callbackPayload())->assertOk();
        $this->assertSame('failed', $p->fresh()->status);
        $this->assertSame('unpaid', $this->transaction->fresh()->payment_status);
        $this->postJson('/payments/callback/test-callback-token', $this->callbackPayload())->assertOk();
        $this->assertSame('paid', $this->transaction->fresh()->payment_status);
    }

    public function test_wrong_callback_token_and_wrong_merchant_reference_are_rejected(): void
    {
        $this->attempt();
        $this->fakeQuery(merchant: 'wrong-merchant');
        $this->postJson('/payments/callback/wrong', $this->callbackPayload())->assertNotFound();
        $this->postJson('/payments/callback/test-callback-token', $this->callbackPayload())->assertOk();
        $this->assertSame('unpaid',$this->transaction->fresh()->payment_status);
    }
}
