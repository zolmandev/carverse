<?php

namespace Tests\Feature;

use App\Models\MotoringEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventApprovalsTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        $this->organizer = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function payload(): array
    {
        $day = today('Africa/Nairobi')->addWeek()->toDateString();

        return ['title' => 'Nairobi owners meetup', 'category' => 'meetup', 'description' => 'A community motoring event.', 'organizer_name' => 'Test Motoring Club', 'contact_email' => 'organizer@example.test', 'contact_phone' => '0712345678', 'venue' => 'Test event venue', 'location' => 'Nairobi', 'starts_at_local' => $day.'T10:00', 'ends_at_local' => $day.'T12:00', 'website_url' => 'https://example.test/event', 'evidence' => UploadedFile::fake()->create('organizer-details.pdf', 1, 'application/pdf'), 'poster' => UploadedFile::fake()->createWithContent('poster.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jN7cAAAAASUVORK5CYII='))];
    }

    private function submit(): MotoringEvent
    {
        $this->actingAs($this->organizer)->post('/events', $this->payload())->assertSessionHasNoErrors()->assertRedirect();

        return MotoringEvent::firstOrFail();
    }

    private function approval(MotoringEvent $event): array
    {
        return ['status' => 'approved', 'version' => $event->version, 'approval_scope' => 'Organizer details, event schedule and venue information reviewed.', 'review_note' => 'Checked supporting evidence.', 'evidence_checked' => 1];
    }

    private function approve(MotoringEvent $event): void
    {
        $this->actingAs($this->admin)->post('/events/'.$event->id.'/review', $this->approval($event))->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_submission_is_pending_and_nairobi_times_are_stored_in_utc(): void
    {
        $this->actingAs($this->organizer)->get('/events/submit')->assertInertia(fn (Assert $p) => $p->component('carverse/event-submission')->where('event', null));
        $event = $this->submit();
        $this->get('/events/'.$event->id.'/edit')->assertInertia(fn (Assert $p) => $p->component('carverse/event-submission')->where('event.id', $event->id));
        $this->assertSame('pending', $event->status);
        $this->assertSame('07:00', $event->starts_at->format('H:i'));
        $this->assertSame('09:00', $event->ends_at->format('H:i'));
        $this->get('/app/events')->assertOk()->assertInertia(fn (Assert $p) => $p->has('events.mine.data', 1)->has('events.upcoming.data', 0));
        $this->post('/logout');
        $this->get('/events')->assertOk()->assertInertia(fn (Assert $p) => $p->has('data.upcoming.data', 0)->where('data.mine', null));
        $this->get('/events/'.$event->id.'/poster')->assertNotFound();
    }

    public function test_manage_my_events_only_shows_the_organizers_submissions_and_filters_status(): void
    {
        $event = $this->submit();
        $this->get('/events/mine')->assertInertia(fn (Assert $p) => $p->component('carverse/my-events')
            ->has('events.data', 1)->where('events.data.0.id', $event->id)
            ->where('counts.pending', 1)->where('status', 'all'));
        $this->get('/events/mine?status=approved')->assertInertia(fn (Assert $p) => $p->has('events.data', 0)->where('status', 'approved'));
        $this->get('/events/mine?status=invalid')->assertSessionHasErrors('status');

        $other = User::factory()->create();
        $this->actingAs($other)->get('/events/mine')->assertInertia(fn (Assert $p) => $p->has('events.data', 0));
        $this->get('/events/'.$event->id.'/edit')->assertForbidden();
        $this->post('/events/'.$event->id.'/withdraw')->assertForbidden();
        $this->post('/logout');
        $this->get('/events/mine')->assertRedirect('/login');
    }

    public function test_admin_can_review_and_publish_without_exposing_contacts_or_evidence(): void
    {
        $event = $this->submit();
        $this->actingAs($this->admin)->get('/app/admin?tab=events')->assertInertia(fn (Assert $p) => $p->where('eventApplications.status', 'pending')->has('eventApplications.events.data', 1)->where('eventApplications.events.data.0.contact_email', 'organizer@example.test')->missing('eventApplications.events.data.0.evidence_path'));
        $this->get('/events/'.$event->id.'/evidence')->assertDownload('organizer-details.pdf');
        $this->approve($event);
        $event->refresh();
        $this->assertSame('approved', $event->status);
        $this->assertSame(1, $event->approved_version);
        $this->assertSame($this->admin->id, $event->reviewed_by);
        $this->post('/logout');
        $this->get('/events')->assertInertia(fn (Assert $p) => $p->has('data.upcoming.data', 1)->where('data.upcoming.data.0.title', 'Nairobi owners meetup')->missing('data.upcoming.data.0.contact_email')->missing('data.upcoming.data.0.contact_phone')->missing('data.upcoming.data.0.user_id')->missing('data.upcoming.data.0.evidence_name')->missing('data.upcoming.data.0.evidence_path')->missing('data.upcoming.data.0.review_note'));
        $this->get('/events/'.$event->id.'/poster')->assertOk();
        $this->get('/events/'.$event->id.'/evidence')->assertRedirect('/login');
        $this->assertDatabaseHas('audit_events', ['action' => 'event.reviewed']);
    }

    public function test_admin_can_edit_event_details_and_replace_poster_without_losing_approval(): void
    {
        $event = $this->submit();
        $this->approve($event);
        $oldPoster = $event->poster_path;
        $data = $this->payload();
        $data['title'] = 'Admin curated Nairobi owners meetup';
        $data['poster'] = UploadedFile::fake()->create('updated-poster.webp', 10, 'image/webp');

        $this->actingAs($this->admin)->put('/admin/events/'.$event->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $event->refresh();

        $this->assertSame('Admin curated Nairobi owners meetup', $event->title);
        $this->assertSame('approved', $event->status);
        $this->assertSame(2, $event->version);
        $this->assertSame(2, $event->approved_version);
        $this->assertNotSame($oldPoster, $event->poster_path);
        $this->assertDatabaseHas('audit_events', ['action' => 'event.admin_updated', 'subject_id' => $event->id]);
        $this->get('/events/'.$event->id.'/poster')->assertOk();
    }

    public function test_non_admins_cannot_approve_or_read_other_organizer_evidence(): void
    {
        $event = $this->submit();
        foreach ([User::factory()->create(), User::factory()->create(['role' => 'moderator'])] as $user) {
            $this->actingAs($user)->post('/events/'.$event->id.'/review', $this->approval($event))->assertForbidden();
            $this->get('/events/'.$event->id.'/evidence')->assertForbidden();
            $this->get('/events/'.$event->id.'/poster')->assertNotFound();
            $this->get('/app/admin?tab=events')->assertForbidden();
            $this->put('/events/'.$event->id, $this->payload())->assertForbidden();
            $this->put('/admin/events/'.$event->id, $this->payload())->assertForbidden();
            $this->post('/events/'.$event->id.'/withdraw')->assertForbidden();
        }$this->actingAs($this->organizer)->get('/events/'.$event->id.'/evidence')->assertDownload('organizer-details.pdf');
    }

    public function test_organizer_edits_reset_approval_and_block_stale_admin_decisions(): void
    {
        $event = $this->submit();
        $oldApproval = $this->approval($event);
        $this->approve($event);
        $data = $this->payload();
        $data['title'] = 'Updated event schedule';
        $this->actingAs($this->organizer)->put('/events/'.$event->id, $data)->assertSessionHasNoErrors();
        $event->refresh();
        $this->assertSame('pending', $event->status);
        $this->assertSame(2, $event->version);
        $this->assertNull($event->approved_version);
        $this->get('/events')->assertInertia(fn (Assert $p) => $p->has('data.upcoming.data', 0));
        $this->actingAs($this->admin)->post('/events/'.$event->id.'/review', $oldApproval)->assertSessionHasErrors('version');
        $this->approve($event);
        $this->get('/events')->assertInertia(fn (Assert $p) => $p->has('data.upcoming.data', 1)->where('data.upcoming.data.0.title', 'Updated event schedule'));
    }

    public function test_approval_requires_scope_available_evidence_and_confirmation(): void
    {
        $event = $this->submit();
        $this->actingAs($this->admin)->post('/events/'.$event->id.'/review', ['status' => 'approved', 'version' => 1, 'review_note' => 'Checked'])->assertSessionHasErrors(['approval_scope', 'evidence_checked']);
        Storage::disk('local')->delete($event->evidence_path);
        $this->post('/events/'.$event->id.'/review', $this->approval($event))->assertSessionHasErrors('evidence_checked');
        $this->assertSame('pending', $event->fresh()->status);
    }

    public function test_rejected_and_withdrawn_events_stay_unpublished(): void
    {
        $event = $this->submit();
        $this->actingAs($this->admin)->post('/events/'.$event->id.'/review', ['status' => 'rejected', 'version' => 1, 'review_note' => 'Organizer must clarify venue details.'])->assertSessionHasNoErrors();
        $this->actingAs($this->organizer)->get('/app/events')->assertInertia(fn (Assert $p) => $p->where('events.mine.data.0.status', 'rejected')->where('events.mine.data.0.review_note', 'Organizer must clarify venue details.'));
        $this->post('/events/'.$event->id.'/withdraw')->assertRedirect();
        $this->actingAs($this->admin)->post('/events/'.$event->id.'/review', $this->approval($event))->assertSessionHasErrors('status');
        $this->get('/events')->assertInertia(fn (Assert $p) => $p->has('data.upcoming.data', 0));
    }

    public function test_invalid_schedule_unsafe_link_and_started_event_are_rejected(): void
    {
        $data = $this->payload();
        $data['ends_at_local'] = $data['starts_at_local'];
        $this->actingAs($this->organizer)->post('/events', $data)->assertSessionHasErrors('ends_at_local');
        $data = $this->payload();
        $data['starts_at_local'] = today('Africa/Nairobi')->subDay()->format('Y-m-d').'T10:00';
        $data['website_url'] = 'javascript:alert(1)';
        $this->post('/events', $data)->assertSessionHasErrors(['starts_at_local', 'website_url']);
        $event = $this->submit();
        $event->update(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $this->actingAs($this->admin)->post('/events/'.$event->id.'/review', $this->approval($event))->assertSessionHasErrors('status');
    }
}
