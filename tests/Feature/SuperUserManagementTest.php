<?php

namespace Tests\Feature;

use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Community;
use App\Models\RacingEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SuperUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_super_user_lands_on_focused_management_page(): void
    {
        $u = User::factory()->create(['role' => 'admin']);
        $this->actingAs($u)->get('/app')->assertRedirect('/app/admin');
        $this->get('/app/admin')->assertOk()->assertInertia(fn (Assert $p) => $p->component('carverse/super-user')->where('tab', 'groups')->missing('admin')->missing('vehicles')->has('groups.data'));
    }

    public function test_admin_can_create_edit_and_remove_whatsapp_links(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $brand = CarBrand::create(['name' => 'Toyota', 'slug' => 'toyota']);
        $model = CarModel::create(['car_brand_id' => $brand->id, 'name' => 'Fielder', 'slug' => 'fielder']);
        $this->post('/communities', ['name' => 'Toyota Kenya', 'car_brand_id' => $brand->id, 'car_model_id' => $model->id, 'url' => 'https://chat.whatsapp.com/ExampleInvite123'])->assertRedirect();
        $g = Community::firstOrFail();
        $this->put('/communities/'.$g->id, ['name' => 'Fielder Owners', 'car_brand_id' => $brand->id, 'car_model_id' => $model->id, 'url' => 'https://chat.whatsapp.com/Replacement123'])->assertRedirect();
        $this->assertSame('Fielder Owners', $g->fresh()->name);
        $this->assertDatabaseHas('audit_events', ['action' => 'community.updated']);
        $this->delete('/communities/'.$g->id)->assertRedirect();
        $this->assertDatabaseMissing('communities', ['id' => $g->id]);
    }

    public function test_other_roles_cannot_manage_group_links_or_racing(): void
    {
        foreach (['owner', 'moderator'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->post('/communities', ['name' => 'X', 'model' => 'Y', 'url' => 'https://chat.whatsapp.com/Test123'])->assertForbidden();
            $this->post('/racing', [])->assertForbidden();
        }
    }

    public function test_untrusted_invitation_domains_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/communities', ['name' => 'Group', 'model' => 'Toyota', 'url' => 'https://chat.whatsapp.com.evil.example/Invite'])->assertSessionHasErrors('url');
        $this->assertDatabaseCount('communities', 0);
    }

    private function createRun(): RacingEntry
    {
        $this->post('/racing', ['driver' => 'Demo Driver', 'vehicle' => 'Demo GTI', 'event' => 'Pilot Cup', 'venue' => 'Demo Circuit', 'race_date' => today()->toDateString(), 'distance_metres' => 402, 'elapsed_seconds' => '12.345', 'gps' => UploadedFile::fake()->create('run.csv', 1, 'text/csv'), 'slip' => UploadedFile::fake()->create('timing.pdf', 1, 'application/pdf')])->assertRedirect('/app/admin?tab=racing');

        return RacingEntry::firstOrFail();
    }

    public function test_run_requires_both_files_and_manual_review_before_ranking(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/racing', [])->assertSessionHasErrors(['gps', 'slip']);
        $run = $this->createRun();
        $this->assertSame('pending', $run->status);
        $this->assertSame(12345, $run->elapsed_ms);
        $url = '/app/admin?tab=racing&event=Pilot%20Cup&distance=402';
        $this->get($url)->assertInertia(fn (Assert $p) => $p->has('leaderboard', 0)->missing('entries.data.0.gps_path')->missing('entries.data.0.slip_path'));
        $this->post('/racing/'.$run->id.'/review', ['status' => 'verified', 'review_note' => 'Checked documents'])->assertSessionHasErrors(['gps_checked', 'slip_checked', 'matching_run']);
        $this->post('/racing/'.$run->id.'/review', ['status' => 'verified', 'review_note' => 'Both evidence sources match the recorded run.', 'gps_checked' => 1, 'slip_checked' => 1, 'matching_run' => 1])->assertRedirect();
        $this->assertSame('verified', $run->fresh()->status);
        $this->assertNotNull($run->fresh()->reviewed_at);
        $this->get($url)->assertInertia(fn (Assert $p) => $p->has('leaderboard', 1)->where('leaderboard.0.elapsed_ms', 12345));
        $this->post('/racing/'.$run->id.'/review', ['status' => 'pending', 'review_note' => 'Reopened for additional review'])->assertRedirect();
        $this->get($url)->assertInertia(fn (Assert $p) => $p->has('leaderboard', 0));
    }

    public function test_racing_evidence_downloads_are_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $run = $this->createRun();
        $this->get('/racing/'.$run->id.'/evidence/gps')->assertDownload('run.csv');
        $this->actingAs(User::factory()->create())->get('/racing/'.$run->id.'/evidence/slip')->assertForbidden();
    }
}
