<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProviderApprovalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    private function logo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('garage-logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jN7cAAAAASUVORK5CYII='));
    }

    private function provider(string $kind = 'garage'): array
    {
        $owner = User::factory()->create();
        $org = Organization::factory()->create(['kind' => $kind, 'status' => 'pending', 'verified_at' => null, 'qualifications' => 'Applicant qualification claim', 'logo_path' => 'provider-logos/test.png']);
        $org->members()->attach($owner);
        Storage::disk('local')->put('provider-logos/test.png', 'test image');
        Storage::disk('local')->put('evidence/application.pdf', 'test evidence');
        $attachment = Attachment::create(['user_id' => $owner->id, 'organization_id' => $org->id, 'name' => 'application.pdf', 'path' => 'evidence/application.pdf']);

        return [$owner, $org, $attachment];
    }

    private function decision(string $status = 'approved'): array
    {
        return ['status' => $status, 'verification_scope' => 'Identity and submitted trade qualification documents reviewed; no guarantee of all workmanship.', 'review_note' => 'Private feedback to the applicant.', 'evidence_checked' => 1];
    }

    public function test_garages_and_mechanics_can_apply_with_logo_and_private_documents(): void
    {
        foreach (['garage', 'mechanic'] as $kind) {
            $owner = User::factory()->create();
            $this->actingAs($owner)->post('/providers/apply', ['name' => 'New '.$kind, 'kind' => $kind, 'location' => 'Nairobi', 'phone' => '0712345678', 'description' => 'Specialist automotive services', 'years_experience' => 5, 'qualifications' => 'Applicant-reported training', 'specialties' => ['Toyota', 'Diagnostics'], 'opening_hours' => 'Monday to Saturday', 'logo' => $this->logo(), 'evidence' => UploadedFile::fake()->create('credentials.pdf', 1, 'application/pdf')])->assertSessionHasNoErrors()->assertRedirect();
            $org = Organization::where('kind', $kind)->firstOrFail();
            $this->assertSame('pending', $org->status);
            $this->assertFalse($owner->inOrganization($org->id));
            $this->assertNotNull($org->logo_url);
            $this->get('/provider-logos/'.$org->id)->assertOk();
            $this->get('/attachments/'.$org->attachments()->firstOrFail()->id)->assertDownload('credentials.pdf');
            $this->actingAs(User::factory()->create())->get('/provider-logos/'.$org->id)->assertNotFound();
        }$this->get('/directory')->assertInertia(fn (Assert $p) => $p->has('providers', 0));
    }

    public function test_admin_can_review_and_approve_both_provider_types(): void
    {
        foreach (['garage', 'mechanic'] as $kind) {
            [$owner,$org,$file] = $this->provider($kind);
            $admin = User::factory()->create(['role' => 'admin']);
            $this->actingAs($admin)->get('/app/admin?tab=approvals&provider_kind='.$kind)->assertInertia(fn (Assert $p) => $p->where('applications.status', 'pending')->has('applications.providers.data', 1)->where('applications.providers.data.0.qualifications', 'Applicant qualification claim'));
            $this->get('/attachments/'.$file->id)->assertDownload('application.pdf');
            $this->post('/providers/'.$org->id.'/review', $this->decision())->assertSessionHasNoErrors()->assertRedirect();
            $this->assertTrue($owner->fresh()->inOrganization($org->id));
            $this->assertSame('approved', $org->fresh()->status);
            $this->assertSame($admin->id, $org->fresh()->reviewed_by);
            $this->assertNotNull($org->fresh()->verified_at);
            $this->assertNotNull($org->fresh()->reviewed_at);
            $this->assertDatabaseHas('audit_events', ['action' => 'provider.reviewed', 'subject_id' => $org->id]);
        }$this->post('/logout');
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->has('providers', 2)->missing('providers.0.qualifications')->missing('providers.0.review_note')->missing('providers.0.logo_path')->missing('providers.0.reviewed_by'));
        $this->get('/provider-logos/'.$org->id)->assertOk();
    }

    public function test_approval_requires_documents_scope_and_review_confirmation(): void
    {
        [$owner,$org] = $this->provider();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/providers/'.$org->id.'/review', ['status' => 'approved', 'review_note' => 'Checked'])->assertSessionHasErrors(['verification_scope', 'evidence_checked']);
        $org->attachments()->delete();
        $this->post('/providers/'.$org->id.'/review', $this->decision())->assertSessionHasErrors('evidence_checked');
        $this->assertSame('pending', $org->fresh()->status);
    }

    public function test_non_admins_cannot_review_or_read_other_applicant_evidence(): void
    {
        [$owner,$org,$file] = $this->provider('mechanic');
        foreach (['owner', 'moderator'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->post('/providers/'.$org->id.'/review', $this->decision())->assertForbidden();
            $this->get('/attachments/'.$file->id)->assertForbidden();
            $this->get('/app/admin?tab=approvals')->assertForbidden();
        }$this->actingAs($owner)->get('/attachments/'.$file->id)->assertDownload('application.pdf');
    }

    public function test_rejection_and_reopening_remove_public_listing_and_staff_access(): void
    {
        [$owner,$org] = $this->provider();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/providers/'.$org->id.'/review', $this->decision())->assertRedirect();
        $this->assertTrue($owner->fresh()->inOrganization($org->id));
        $this->post('/providers/'.$org->id.'/review', $this->decision('rejected'))->assertRedirect();
        $this->assertFalse($owner->fresh()->inOrganization($org->id));
        $this->assertNull($org->fresh()->verified_at);
        $this->get('/directory')->assertInertia(fn (Assert $p) => $p->has('providers', 0));
        $this->post('/providers/'.$org->id.'/review', $this->decision('pending'))->assertRedirect();
        $this->assertSame('pending', $org->fresh()->status);
        $this->assertDatabaseCount('audit_events', 3);
        $this->actingAs($owner)->get('/app/provider')->assertInertia(fn (Assert $p) => $p->where('organizations.0.review_note', 'Private feedback to the applicant.'));
    }

    public function test_garage_members_can_update_logo_but_other_users_cannot(): void
    {
        [$owner,$org] = $this->provider();
        $this->actingAs($owner)->post('/providers/'.$org->id.'/logo', ['logo' => $this->logo()])->assertSessionHasNoErrors()->assertRedirect();
        $path = $org->fresh()->logo_path;
        Storage::disk('local')->assertExists($path);
        $this->assertArrayNotHasKey('logo_path', $org->fresh()->toArray());
        $this->post('/providers/'.$org->id.'/logo', ['logo' => UploadedFile::fake()->createWithContent('unsafe.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')])->assertSessionHasErrors('logo');
        $this->actingAs(User::factory()->create())->post('/providers/'.$org->id.'/logo', ['logo' => $this->logo()])->assertForbidden();
        $this->assertSame($path,$org->fresh()->logo_path);
    }
}
