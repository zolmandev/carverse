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

    private User $enthusiast;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        $this->enthusiast = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function payload(): array
    {
        $day = today('Africa/Nairobi')->addWeek()->toDateString();

        return [
            'title' => 'Nairobi owners meetup',
            'category' => 'meetup',
            'description' => 'A community motoring event.',
            'organizer_name' => 'Carverse Events',
            'contact_email' => 'events@example.test',
            'contact_phone' => '0712345678',
            'venue' => 'Test event venue',
            'location' => 'Nairobi',
            'starts_at_local' => $day.'T10:00',
            'ends_at_local' => $day.'T12:00',
            'website_url' => 'https://example.test/event',
            'poster' => UploadedFile::fake()->create('poster.png', 1, 'image/png'),
            'evidence' => UploadedFile::fake()->create('event-details.pdf', 1, 'application/pdf'),
        ];
    }

    public function test_enthusiasts_cannot_create_or_manage_events(): void
    {
        $this->actingAs($this->enthusiast)->get('/events/submit')->assertNotFound();
        $this->actingAs($this->enthusiast)->get('/events/mine')->assertNotFound();
        $this->actingAs($this->enthusiast)->post('/events', $this->payload())->assertMethodNotAllowed();
        $this->actingAs($this->enthusiast)->put('/events/1', $this->payload())->assertNotFound();
        $this->actingAs($this->enthusiast)->post('/events/1/withdraw')->assertNotFound();
    }

    public function test_events_page_only_exposes_public_discovery_data(): void
    {
        $this->actingAs($this->enthusiast)->get('/events')->assertInertia(fn (Assert $page) => $page
            ->component('carverse/events')
            ->has('data.upcoming')
            ->missing('data.mine')
            ->missing('data.canSubmit'));
    }

    public function test_admin_can_create_and_publish_an_event_from_the_admin_panel(): void
    {
        $this->actingAs($this->enthusiast)->post('/admin/events', $this->payload())->assertForbidden();

        $this->actingAs($this->admin)->post('/admin/events', $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $event = MotoringEvent::firstOrFail();

        $this->assertSame('approved', $event->status);
        $this->assertSame(1, $event->approved_version);
        $this->assertSame($this->admin->id, $event->reviewed_by);
        $this->assertDatabaseHas('audit_events', ['action' => 'event.admin_created', 'subject_id' => $event->id]);
        $this->get('/events')->assertInertia(fn (Assert $page) => $page->where('data.upcoming.data.0.title', 'Nairobi owners meetup'));
    }

    public function test_admin_can_manage_events_from_the_admin_panel(): void
    {
        $this->actingAs($this->admin)->post('/admin/events', $this->payload())->assertRedirect();
        $event = MotoringEvent::firstOrFail();

        $data = $this->payload();
        $data['title'] = 'Admin curated Nairobi owners meetup';
        $this->actingAs($this->admin)->put('/admin/events/'.$event->id, $data)->assertSessionHasNoErrors()->assertRedirect();

        $event->refresh();
        $this->assertSame('Admin curated Nairobi owners meetup', $event->title);
        $this->assertSame('approved', $event->status);
        $this->assertSame(2, $event->version);
        $this->assertSame(2, $event->approved_version);
    }
}
