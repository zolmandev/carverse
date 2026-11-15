<?php

namespace Tests\Feature;

use App\Models\MotoringEvent;
use App\Models\RacingEntry;
use App\Models\User;
use Database\Seeders\LeaderboardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_landing_events_exclude_unapproved_expired_and_stale_versions_and_private_fields(): void
    {
        $owner = User::factory()->create();
        $data = ['user_id' => $owner->id, 'title' => 'Community car show', 'category' => 'car_show', 'description' => 'Meet local enthusiasts.', 'organizer_name' => 'Community', 'contact_email' => 'private@example.test', 'contact_phone' => '0712345678', 'venue' => 'Test venue', 'location' => 'Nairobi', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2), 'evidence_path' => 'private/proof.pdf', 'evidence_name' => 'proof.pdf', 'poster_path' => 'private/poster.png', 'status' => 'approved', 'version' => 1, 'approved_version' => 1];
        $event = MotoringEvent::create($data);
        MotoringEvent::create(array_replace($data, ['status' => 'pending']));
        MotoringEvent::create(array_replace($data, ['version' => 2]));
        MotoringEvent::create(array_replace($data, ['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]));
        $this->get('/')->assertInertia(fn (Assert $p) => $p->has('upcomingEvents', 1)->where('upcomingEvents.0.id', $event->id)->where('upcomingEvents.0.description', 'Meet local enthusiasts.')->has('upcomingEvents.0.poster_url')->missing('upcomingEvents.0.contact_email')->missing('upcomingEvents.0.poster_path')->missing('upcomingEvents.0.evidence_path'));
    }

    public function test_public_leaderboard_scopes_distance_event_and_verified_status_and_hides_evidence(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $data = ['created_by' => $admin->id, 'driver' => 'Fictional Driver', 'vehicle' => 'Test coupe', 'event' => 'Test meet A', 'venue' => 'Closed test circuit', 'race_date' => today(), 'distance_metres' => 402, 'elapsed_ms' => 11000, 'gps_path' => 'private/run.gpx', 'gps_name' => 'run.gpx', 'slip_path' => 'private/slip.pdf', 'slip_name' => 'slip.pdf', 'status' => 'verified', 'review_note' => 'Private review'];
        $quarter = RacingEntry::create($data);
        $half = RacingEntry::create(array_replace($data, ['distance_metres' => 805, 'elapsed_ms' => 21000]));
        $pending = RacingEntry::create(array_replace($data, ['status' => 'pending', 'elapsed_ms' => 9000]));
        RacingEntry::create(array_replace($data, ['event' => 'Test meet B', 'elapsed_ms' => 10000]));
        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('racing.distance', 402)->where('racing.event', 'Test meet A')->has('racing.runs', 2)->where('racing.runs.0.id', $pending->id)->missing('racing.runs.0.gps_path')->missing('racing.runs.0.review_note')->missing('racing.runs.0.created_by'));
        $this->get('/?race_distance=805')->assertInertia(fn (Assert $p) => $p->where('racing.distance', 805)->has('racing.runs', 1)->where('racing.runs.0.id', $half->id));
        $this->get('/?race_distance=201')->assertInertia(fn (Assert $p) => $p->has('racing.runs', 0)->has('racing.events', 0));
        $this->get('/?race_distance=123')->assertSessionHasErrors('race_distance');
        $this->actingAs($admin)->get('/app/admin?tab=racing&distance=805')->assertOk();
    }

    public function test_demo_leaderboard_seed_is_idempotent_and_real_results_take_priority(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->seed(LeaderboardSeeder::class);
        $this->seed(LeaderboardSeeder::class);
        $this->assertSame(15, RacingEntry::where('is_demo', true)->count());
        foreach ([201, 402, 805] as $distance) {
            $this->get('/?race_distance='.$distance)->assertInertia(fn (Assert $p) => $p->where('racing.is_demo', true)->has('racing.runs', 5));
        }
        $fixture = RacingEntry::firstOrFail();
        $real = $fixture->replicate();
        $real->is_demo = false;
        $real->event = 'Actual test fixture';
        $real->driver = 'Test driver';
        $real->save();
        $this->get('/?race_distance='.$real->distance_metres)->assertInertia(fn (Assert $p) => $p->where('racing.is_demo', false)->has('racing.runs', 1)->where('racing.runs.0.id', $real->id)->has('racing.events', 1));
    }
}
