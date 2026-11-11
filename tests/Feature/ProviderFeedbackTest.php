<?php

namespace Tests\Feature;

use App\Actions\Workflow;
use App\Models\Attachment;
use App\Models\Complaint;
use App\Models\Organization;
use App\Models\Quote;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProviderFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private User $staff;

    private Organization $garage;

    private Transaction $transaction;

    private Review $review;

    private Complaint $case;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        $this->owner = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = User::factory()->create();
        $this->garage = Organization::factory()->create();
        $this->garage->members()->attach($this->staff);
        $v = Vehicle::factory()->create(['user_id' => $this->owner->id]);
        $request = ServiceRequest::create(['user_id' => $this->owner->id, 'vehicle_id' => $v->id, 'organization_id' => $this->garage->id, 'kind' => 'service', 'title' => 'Service', 'notes' => 'Test', 'status' => 'quoted']);
        $quote = Quote::create(['service_request_id' => $request->id, 'organization_id' => $this->garage->id, 'items' => [['description' => 'Labor', 'amount_minor' => 100000]], 'total_minor' => 100000, 'valid_until' => today()->addWeek(), 'duration' => '1 hour']);
        $this->transaction = app(Workflow::class)->accept($quote, $this->owner);
        $this->transaction->update(['status' => 'completed', 'completed_at' => now()]);
        $this->review = Review::create(['user_id' => $this->owner->id, 'transaction_id' => $this->transaction->id, 'quality' => 2, 'communication' => 3, 'timeliness' => 4, 'value' => 2, 'body' => 'Work needed a follow-up.']);
        $this->case = Complaint::create(['user_id' => $this->owner->id, 'transaction_id' => $this->transaction->id, 'body' => 'Private case evidence and report.']);
        Storage::disk('local')->put('evidence/case.pdf', 'test evidence');
        Attachment::create(['user_id' => $this->owner->id, 'complaint_id' => $this->case->id, 'path' => 'evidence/case.pdf', 'name' => 'case.pdf']);
    }

    private function base(): string
    {
        return '/providers/'.$this->garage->id;
    }

    private function admin(): void
    {
        $this->actingAs($this->admin);
    }

    private function approveReview(): void
    {
        $this->post($this->base().'/reviews/'.$this->review->id.'/moderate', ['moderation_status' => 'approved', 'moderation_note' => 'Eligible transaction-linked review; checked content.', 'content_checked' => 1])->assertSessionHasNoErrors();
    }

    private function approveCase(): void
    {
        $this->post($this->base().'/cases/'.$this->case->id.'/moderate', ['moderation_status' => 'approved', 'moderation_note' => 'Reviewed private evidence.', 'evidence_checked' => 1])->assertSessionHasNoErrors();
    }

    public function test_approved_garages_show_review_case_counts_and_scoped_detail(): void
    {
        $this->admin();
        $this->get('/app/admin?tab=approvals&provider_status=approved')->assertInertia(fn (Assert $p) => $p->where('applications.providers.data.0.reviews_count', 1)->where('applications.providers.data.0.pending_cases_count', 1));
        $this->get('/app/admin?tab=approvals&provider_status=approved&provider_id='.$this->garage->id.'&feedback=cases')->assertOk()->assertInertia(fn (Assert $p) => $p->has('applications.feedback.reviews.data', 1)->has('applications.feedback.cases.data', 1)->where('applications.feedback.provider.id', $this->garage->id)->missing('applications.feedback.cases.data.0.attachments.0.path'));
    }

    public function test_only_approved_reviews_count_publicly_and_ratings_cannot_be_rewritten(): void
    {
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->where('providers.0.review_count', 0));
        $this->admin();
        $this->approveReview();
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->where('providers.0.review_count', 1)->where('providers.0.ratings.quality', 2)->where('providers.0.public_reviews.0.body', 'Work needed a follow-up.')->missing('providers.0.public_reviews.0.moderation_note'));
        $this->post($this->base().'/reviews/'.$this->review->id.'/moderate', ['moderation_status' => 'rejected', 'moderation_note' => 'Requires correction of private details.', 'quality' => 5])->assertSessionHasNoErrors();
        $this->assertSame(2, $this->review->fresh()->quality);
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->where('providers.0.review_count', 0));
        $this->assertDatabaseHas('audit_events', ['action' => 'review.moderated']);
    }

    public function test_provider_reply_waits_for_approval_without_changing_rating_count(): void
    {
        $this->admin();
        $this->approveReview();
        $this->actingAs($this->staff)->post('/reviews/'.$this->review->id.'/response', ['response' => 'We followed up with the customer.'])->assertRedirect();
        $this->assertSame('approved', $this->review->fresh()->moderation_status);
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->where('providers.0.review_count', 1)->where('providers.0.public_reviews.0.response', null));
        $this->admin();
        $this->approveReview();
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->where('providers.0.public_reviews.0.response', 'We followed up with the customer.'));
    }

    public function test_ineligible_or_cross_provider_reviews_cannot_be_approved(): void
    {
        $this->admin();
        $this->transaction->update(['status' => 'in_progress']);
        $this->post($this->base().'/reviews/'.$this->review->id.'/moderate', ['moderation_status' => 'approved', 'moderation_note' => 'Review', 'content_checked' => 1])->assertSessionHasErrors('moderation_status');
        $other = Organization::factory()->create();
        $this->post('/providers/'.$other->id.'/reviews/'.$this->review->id.'/moderate', ['moderation_status' => 'rejected', 'moderation_note' => 'Wrong provider'])->assertNotFound();
        $this->post('/providers/'.$other->id.'/cases/'.$this->case->id.'/moderate', ['moderation_status' => 'rejected', 'moderation_note' => 'Wrong provider'])->assertNotFound();
    }

    public function test_case_approval_stays_private_and_public_summary_requires_resolution_and_redaction(): void
    {
        $this->admin();
        $this->approveCase();
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->has('summaries', 0));
        $payload = ['action' => 'publish', 'public_summary' => 'Documentation issue reviewed and resolved.', 'reason' => 'Reviewed resolution', 'redaction_confirmed' => 1];
        $this->post($this->base().'/cases/'.$this->case->id.'/publication', $payload)->assertSessionHasErrors('public_summary');
        $this->post($this->base().'/cases/'.$this->case->id.'/decision', ['status' => 'resolved', 'decision' => 'Reviewed evidence and provider response; issue resolved.'])->assertSessionHasNoErrors();
        $this->post($this->base().'/cases/'.$this->case->id.'/publication', array_diff_key($payload, ['redaction_confirmed' => true]))->assertSessionHasErrors('redaction_confirmed');
        $this->post($this->base().'/cases/'.$this->case->id.'/publication', $payload)->assertSessionHasNoErrors();
        $this->assertSame($this->admin->id, $this->case->fresh()->public_approved_by);
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->has('summaries', 1)->where('summaries.0.public_summary', 'Documentation issue reviewed and resolved.')->missing('summaries.0.body'));
        $this->post($this->base().'/cases/'.$this->case->id.'/publication', ['action' => 'unpublish', 'reason' => 'Further review needed.'])->assertSessionHasNoErrors();
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->has('summaries', 0));
    }

    public function test_case_evidence_and_admin_role_are_required(): void
    {
        $this->admin();
        $this->case->attachments()->delete();
        $this->post($this->base().'/cases/'.$this->case->id.'/moderate', ['moderation_status' => 'approved', 'moderation_note' => 'Review', 'evidence_checked' => 1])->assertSessionHasErrors('evidence_checked');
        foreach ([$this->owner, $this->staff, User::factory()->create(['role' => 'moderator'])] as $u) {
            $this->actingAs($u)->post($this->base().'/reviews/'.$this->review->id.'/moderate', ['moderation_status' => 'approved', 'moderation_note' => 'Review', 'content_checked' => 1])->assertForbidden();
            $this->post($this->base().'/cases/'.$this->case->id.'/moderate', ['moderation_status' => 'approved', 'moderation_note' => 'Review', 'evidence_checked' => 1])->assertForbidden();
            $this->get('/app/admin?tab=approvals&provider_status=approved&provider_id='.$this->garage->id.'&feedback=cases')->assertForbidden();
        }
    }

    public function test_appeal_withdraws_existing_summary_and_reopens_moderation(): void
    {
        $this->admin();
        $this->approveCase();
        $this->post($this->base().'/cases/'.$this->case->id.'/decision', ['status' => 'resolved', 'decision' => 'Initial decision'])->assertRedirect();
        $this->post($this->base().'/cases/'.$this->case->id.'/publication', ['action' => 'publish', 'public_summary' => 'Redacted summary', 'reason' => 'Resolution reviewed', 'redaction_confirmed' => 1])->assertRedirect();
        $this->actingAs($this->owner)->post('/complaints/'.$this->case->id, ['action' => 'appeal', 'appeal' => 'Additional evidence should be considered.'])->assertRedirect();
        $this->assertNull($this->case->fresh()->public_summary);
        $this->assertSame('pending', $this->case->fresh()->moderation_status);
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->has('summaries',0));
    }
}
