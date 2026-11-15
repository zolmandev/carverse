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

    public function test_enthusiasts_cannot_manage_group_links_or_racing(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/communities', ['name' => 'X', 'model' => 'Y', 'url' => 'https://chat.whatsapp.com/Test123'])->assertForbidden();
        $this->post('/racing', [])->assertForbidden();
    }

    public function test_only_admins_can_manage_active_roles_and_seller_requires_approval(): void
    {
        $member = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($member)->post('/users/'.$member->id.'/role', ['role' => 'admin'])->assertForbidden();
        $this->actingAs($admin)->post('/users/'.$member->id.'/role', ['role' => 'moderator'])->assertSessionHasErrors('role');
        $this->post('/users/'.$member->id.'/role', ['role' => 'owner'])->assertSessionHasErrors('role');
        $this->post('/users/'.$member->id.'/role', ['role' => 'seller'])->assertSessionHasErrors('role');
        $this->post('/users/'.$member->id.'/role', ['role' => 'admin'])->assertRedirect();
        $this->assertSame('admin', $member->fresh()->role);
        $this->post('/users/'.$member->id.'/role', ['role' => 'enthusiast'])->assertRedirect();
        $this->assertSame('enthusiast', $member->fresh()->role);
    }

    public function test_user_management_lists_and_filters_accounts_for_admins_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['name' => 'Baraka Otieno', 'email' => 'baraka@example.test']);
        User::factory()->create(['name' => 'Amani Wanjiru', 'email' => 'amani@example.test']);
        User::factory()->create(['name' => 'Seller Account', 'email' => 'seller@example.test', 'role' => 'seller']);

        $this->actingAs($admin)->get('/app/admin?tab=users&q=baraka&user_role=enthusiast')
            ->assertInertia(fn (Assert $page) => $page->component('carverse/super-user')
                ->where('tab', 'users')->has('users.accounts.data', 1)
                ->where('users.accounts.data.0.id', $member->id)
                ->where('users.accounts.data.0.role', 'enthusiast')
                ->missing('users.accounts.data.0.profile_image_path')
                ->where('users.counts.enthusiast', 2)
                ->where('users.counts.seller', 1)
                ->where('users.counts.admin', 1));
        $this->get('/app/admin?tab=users&user_role=invalid')->assertSessionHasErrors('user_role');
        $this->actingAs($member)->get('/app/admin?tab=users')->assertForbidden();
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

    public function test_run_is_published_immediately_and_can_still_be_moderated(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/racing', [])->assertSessionHasErrors(['gps', 'slip']);
        $run = $this->createRun();
        $this->assertSame('verified', $run->status);
        $this->assertSame(12345, $run->elapsed_ms);
        $url = '/app/admin?tab=racing&event=Pilot%20Cup&distance=402';
        $this->get($url)->assertInertia(fn (Assert $p) => $p->has('leaderboard', 1)->missing('entries.data.0.gps_path')->missing('entries.data.0.slip_path'));
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
