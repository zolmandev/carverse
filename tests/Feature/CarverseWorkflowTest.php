<?php

namespace Tests\Feature;

use App\Actions\Workflow;
use App\Jobs\SendMaintenanceReminder;
use App\Models\Attachment;
use App\Models\Complaint;
use App\Models\HistoryShare;
use App\Models\Organization;
use App\Models\Part;
use App\Models\PaymentAttempt;
use App\Models\Quote;
use App\Models\RecordRevision;
use App\Models\Reminder;
use App\Models\ServiceRecord;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CarverseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $staff;

    private Vehicle $vehicle;

    private Organization $org;

    private ServiceRequest $request;

    private Quote $quote;

    protected function setUp(): void
    {
        parent::setUp();
        config(['carverse.legacy_workflows' => true]);
        $this->withoutVite();
        $this->owner = User::factory()->create();
        $this->staff = User::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['user_id' => $this->owner->id]);
        $this->org = Organization::factory()->create();
        $this->org->members()->attach($this->staff);
        $this->request = ServiceRequest::create(['user_id' => $this->owner->id, 'vehicle_id' => $this->vehicle->id, 'organization_id' => $this->org->id, 'kind' => 'service', 'title' => 'Oil service', 'notes' => 'Quote oil and filter', 'status' => 'quoted']);
        $this->quote = Quote::create(['service_request_id' => $this->request->id, 'organization_id' => $this->org->id, 'items' => [['description' => 'Labor', 'amount_minor' => 200000]], 'total_minor' => 200000, 'valid_until' => today()->addDays(7), 'duration' => '2 hours']);
    }

    private function accepted(): Transaction
    {
        return app(Workflow::class)->accept($this->quote, $this->owner);
    }

    public function test_full_owner_provider_demo_payment_completion_record_review_and_reminder_flow(): void
    {
        $this->actingAs($this->owner)->post('/quotes/'.$this->quote->id.'/accept')->assertRedirect();
        $t = Transaction::firstOrFail();
        $this->post('/transactions/'.$t->id.'/pay', ['phone' => '0712345678'])->assertRedirect();
        $this->assertSame('paid', $t->fresh()->payment_status);
        $this->actingAs($this->staff)->post('/transactions/'.$t->id.'/status', ['status' => 'in_progress'])->assertRedirect();
        $this->post('/transactions/'.$t->id.'/status', ['status' => 'awaiting_confirmation'])->assertRedirect();
        $this->actingAs($this->owner)->post('/transactions/'.$t->id.'/status', ['status' => 'completed'])->assertRedirect();
        $this->assertDatabaseHas('service_records', ['transaction_id' => $t->id, 'vehicle_id' => $this->vehicle->id]);
        $this->post('/transactions/'.$t->id.'/reviews', ['quality' => 5, 'communication' => 4, 'timeliness' => 4, 'value' => 5, 'body' => 'Work explained well.'])->assertRedirect();
        $this->assertDatabaseCount('reviews', 1);
        $this->post('/reminders', ['vehicle_id' => $this->vehicle->id, 'title' => 'Oil and filters', 'due_date' => today()->toDateString()])->assertRedirect();
        $reminder = Reminder::firstOrFail();
        (new SendMaintenanceReminder($reminder->id))->handle();
        (new SendMaintenanceReminder($reminder->id))->handle();
        $this->assertDatabaseCount('notifications', 1);
        $this->assertNotNull($reminder->fresh()->notified_at);
    }

    public function test_owner_can_add_imported_chassis_identifier_and_another_owner_cannot_edit(): void
    {
        $payload = ['make' => 'Toyota', 'model' => 'Vitz', 'year' => 2015, 'identifier' => 'KSP130-1234567', 'fuel_type' => 'Petrol', 'transmission' => 'CVT', 'mileage' => 42000, 'mileage_date' => today()->toDateString()];
        $this->actingAs($this->owner)->post('/vehicles', $payload)->assertRedirect();
        $this->assertDatabaseCount('vehicles', 2);
        $other = User::factory()->create();
        $this->actingAs($other)->put('/vehicles/'.$this->vehicle->id, $payload)->assertForbidden();
    }

    public function test_requests_cannot_use_another_owners_vehicle(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other)->post('/requests', ['vehicle_id' => $this->vehicle->id, 'kind' => 'service', 'title' => 'A request', 'notes' => 'Test'])->assertForbidden();
    }

    public function test_cross_organization_quote_and_fulfilment_access_is_forbidden(): void
    {
        $outsider = User::factory()->create();
        $other = Organization::factory()->create();
        $other->members()->attach($outsider);
        $this->actingAs($outsider)->post('/requests/'.$this->request->id.'/quotes', ['organization_id' => $other->id, 'items' => [['description' => 'Labor', 'amount_minor' => 10000]], 'valid_until' => today()->addDay()->toDateString(), 'duration' => '1 hour'])->assertForbidden();
        $t = $this->accepted();
        $this->post('/transactions/'.$t->id.'/status', ['status' => 'in_progress'])->assertForbidden();
    }

    public function test_acceptance_snapshots_terms_and_rejects_duplicate_acceptance(): void
    {
        $t = $this->accepted();
        $this->quote->update(['items' => [['description' => 'Changed', 'amount_minor' => 999999]], 'total_minor' => 999999]);
        $this->assertSame(200000, $t->fresh()->total_minor);
        $this->assertSame('Labor', $t->fresh()->quote_snapshot['items'][0]['description']);
        $this->actingAs($this->owner)->post('/quotes/'.$this->quote->id.'/accept')->assertSessionHasErrors('quote');
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_another_owner_cannot_accept_a_quote(): void
    {
        $this->actingAs(User::factory()->create())->post('/quotes/'.$this->quote->id.'/accept')->assertForbidden();
    }

    public function test_invalid_transition_and_provider_self_completion_are_rejected(): void
    {
        $t = $this->accepted();
        $this->actingAs($this->owner)->post('/transactions/'.$t->id.'/status', ['status' => 'completed'])->assertStatus(422);
        $t->update(['status' => 'awaiting_confirmation']);
        $this->actingAs($this->staff)->post('/transactions/'.$t->id.'/status', ['status' => 'completed'])->assertForbidden();
    }

    public function test_expired_quote_cannot_be_accepted(): void
    {
        $this->quote->update(['valid_until' => today()->subDay()]);
        $this->actingAs($this->owner)->post('/quotes/'.$this->quote->id.'/accept')->assertSessionHasErrors('quote');
    }

    public function test_private_attachment_requires_its_own_authorization(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('evidence/invoice.pdf', 'test invoice');
        $rec = $this->record();
        $a = Attachment::create(['user_id' => $this->owner->id, 'service_record_id' => $rec->id, 'path' => 'evidence/invoice.pdf', 'name' => 'invoice.pdf']);
        $this->actingAs(User::factory()->create())->get('/attachments/'.$a->id)->assertForbidden();
        $this->actingAs($this->owner)->get('/attachments/'.$a->id)->assertDownload('invoice.pdf');
    }

    private function record(): ServiceRecord
    {
        return ServiceRecord::create(['vehicle_id' => $this->vehicle->id, 'author_id' => $this->owner->id, 'service_date' => today(), 'mileage' => 87000, 'work' => 'Oil change']);
    }

    public function test_share_is_selected_redacted_expiring_and_revocable(): void
    {
        $rec = $this->record();
        $other = $this->record();
        $token = str_repeat('a', 64);
        $share = HistoryShare::create(['vehicle_id' => $this->vehicle->id, 'token_hash' => hash('sha256', $token), 'record_ids' => [$rec->id], 'expires_at' => now()->addDay()]);
        $this->get('/history/'.$token)->assertOk()->assertInertia(fn (Assert $p) => $p->component('carverse/shared')->has('records', 1)->missing('vehicle.identifier')->missing('vehicle.user_id')->missing('records.0.attachments'));
        $this->actingAs($this->owner)->delete('/shares/'.$share->id)->assertRedirect();
        $this->get('/history/'.$token)->assertNotFound();
        $share->update(['revoked_at' => null, 'expires_at' => now()->subMinute()]);
        $this->get('/history/'.$token)->assertNotFound();
    }

    public function test_share_cannot_include_another_vehicles_records(): void
    {
        $rec = $this->record();
        $foreign = Vehicle::factory()->create();
        $rec->update(['vehicle_id' => $foreign->id]);
        $this->actingAs($this->owner)->post('/vehicles/'.$this->vehicle->id.'/shares', ['record_ids' => [$rec->id], 'days' => 7])->assertForbidden();
    }

    public function test_fitment_requires_evidence_and_stale_fingerprints_invalidate_confirmation(): void
    {
        $this->org->update(['kind' => 'supplier']);
        $part = Part::create(['organization_id' => $this->org->id, 'title' => 'Filter', 'brand' => 'Denso', 'category' => 'Filters', 'description' => 'Oil filter', 'condition' => 'New', 'stock' => 'In stock']);
        $this->actingAs($this->staff)->post('/parts/'.$part->id.'/fitment', ['vehicle_id' => $this->vehicle->id, 'status' => 'confirmed'])->assertSessionHasErrors('evidence');
        $this->post('/parts/'.$part->id.'/fitment', ['vehicle_id' => $this->vehicle->id, 'status' => 'confirmed', 'evidence' => 'Catalog reference and exact engine/chassis checked'])->assertRedirect();
        $this->actingAs($this->owner)->get('/app/parts')->assertInertia(fn (Assert $p) => $p->where('parts.data.0.fitment_status', 'confirmed'));
        $this->vehicle->update(['engine_code' => 'Different engine']);
        $this->get('/app/parts')->assertInertia(fn (Assert $p) => $p->where('parts.data.0.fitment_status', 'unknown'));
    }

    public function test_corrections_preserve_previous_version_and_reason(): void
    {
        $rec = $this->record();
        $this->actingAs($this->owner)->post('/records/'.$rec->id.'/correct', ['work' => 'Oil and filter change', 'reason' => 'Added omitted filter'])->assertRedirect();
        $this->assertSame(2, $rec->fresh()->version);
        $rev = RecordRevision::firstOrFail();
        $this->assertSame('Oil change', $rev->previous['work']);
        $this->assertSame('Added omitted filter', $rev->reason);
        $this->assertDatabaseHas('audit_events', ['action' => 'record.corrected']);
    }

    public function test_reviews_require_completed_owned_transaction_and_are_unique(): void
    {
        $t = $this->accepted();
        $data = ['quality' => 5, 'communication' => 5, 'timeliness' => 5, 'value' => 5, 'body' => 'Helpful'];
        $this->actingAs($this->owner)->post('/transactions/'.$t->id.'/reviews', $data)->assertStatus(422);
        $t->update(['status' => 'completed']);
        $this->actingAs(User::factory()->create())->post('/transactions/'.$t->id.'/reviews', $data)->assertForbidden();
        $this->actingAs($this->owner)->post('/transactions/'.$t->id.'/reviews', $data)->assertRedirect();
        $this->post('/transactions/'.$t->id.'/reviews', $data)->assertStatus(422);
    }

    public function test_only_moderators_can_intentionally_publish_resolved_complaint(): void
    {
        $t = $this->accepted();
        $c = Complaint::create(['user_id' => $this->owner->id, 'transaction_id' => $t->id, 'body' => 'Private complaint', 'status' => 'resolved', 'decision' => 'Evidence reviewed']);
        $this->actingAs($this->staff)->post('/complaints/'.$c->id, ['action' => 'publish', 'public_summary' => 'Redacted summary'])->assertForbidden();
        $m = User::factory()->create(['role' => 'moderator']);
        $this->actingAs($m)->post('/complaints/'.$c->id, ['action' => 'publish', 'public_summary' => 'Redacted summary'])->assertRedirect();
        $this->assertDatabaseHas('audit_events', ['action' => 'complaint.publish']);
        $this->post('/providers/'.$this->org->id.'/review', ['status' => 'approved', 'verification_scope' => 'Reviewed'])->assertForbidden();
        $this->post('/transactions/'.$t->id.'/finance', ['commission_minor' => 100, 'variable_cost_minor' => 1, 'fitment_return' => false])->assertForbidden();
    }

    public function test_payment_mismatches_and_duplicate_callbacks_do_not_corrupt_payment_state(): void
    {
        $t = $this->accepted();
        $p = PaymentAttempt::create(['transaction_id' => $t->id, 'reference' => 'expected', 'provider' => 'daraja', 'provider_reference' => 'checkout-1', 'amount_minor' => 200000]);
        $w = app(Workflow::class);
        foreach ([[199999, 'KES', 'checkout-1'], [200000, 'USD', 'checkout-1'], [200000, 'KES', 'wrong-checkout']] as [$amount,$currency,$ref]) {
            try {
                $w->reconcile('expected', $ref, $amount, $currency, true);
                $this->fail('Mismatch accepted');
            } catch (HttpException $e) {
                $this->assertSame(422, $e->getStatusCode());
            }
        }
        $w->reconcile('expected', 'checkout-1', 200000, 'KES', true);
        $w->reconcile('expected', 'checkout-1', 200000, 'KES', true);
        $w->reconcile('expected', 'checkout-1', 200000, 'KES', false);
        $this->assertSame('paid', $t->fresh()->payment_status);
        $this->assertSame('paid', $p->fresh()->status);
        $this->assertDatabaseCount('payment_attempts', 1);
    }

    public function test_mileage_reminder_waits_for_recorded_mileage_and_preferences(): void
    {
        $r = Reminder::create(['vehicle_id' => $this->vehicle->id, 'title' => 'Brakes', 'due_mileage' => 90000]);
        (new SendMaintenanceReminder($r->id))->handle();
        $this->assertNull($r->fresh()->notified_at);
        $this->vehicle->update(['mileage' => 90001]);
        $this->owner->update(['reminder_notifications' => false]);
        (new SendMaintenanceReminder($r->id))->handle();
        $this->assertNull($r->fresh()->notified_at);
        $this->owner->update(['reminder_notifications' => true]);
        (new SendMaintenanceReminder($r->id))->handle();
        $this->assertNotNull($r->fresh()->notified_at);
    }

    public function test_admin_and_provider_screens_render_without_exposing_other_owner_identifiers(): void
    {
        $this->actingAs($this->owner)->get('/app')->assertOk();
        $this->actingAs($this->staff)->get('/app/provider')->assertOk()->assertInertia(fn (Assert $p) => $p->missing('requests.data.0.vehicle.identifier'));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/app/admin')->assertOk();
    }
}
