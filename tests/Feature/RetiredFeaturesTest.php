<?php

namespace Tests\Feature;

use App\Jobs\SendMaintenanceReminder;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RetiredFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_retired_pages_and_submission_routes_are_unavailable(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['/app/requests', '/app/passport', '/app/reminders', '/history/old-link'] as $url) {
            $this->get($url)->assertNotFound();
        }
        foreach (['/requests', '/records', '/reminders'] as $url) {
            $this->post($url, [])->assertNotFound();
        }
    }

    public function test_workspace_does_not_send_retired_feature_data(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create())->get('/app')->assertOk()->assertInertia(fn (Assert $p) => $p->missing('requests')->missing('records')->missing('reminders')->missing('shares')->missing('notifications'));
    }

    public function test_queued_maintenance_jobs_no_longer_send_notifications(): void
    {
        $u = User::factory()->create();
        $v = Vehicle::factory()->create(['user_id' => $u->id]);
        $r = Reminder::create(['vehicle_id' => $v->id, 'title' => 'Old reminder', 'due_date' => today()]);
        (new SendMaintenanceReminder($r->id))->handle();
        $this->assertDatabaseCount('notifications', 0);
        $this->assertNull($r->fresh()->notified_at);
    }
}
