<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Fitment;
use App\Models\HistoryShare;
use App\Models\Organization;
use App\Models\Part;
use App\Models\Quote;
use App\Models\Reminder;
use App\Models\ServiceRecord;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarverseOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_vehicle_request_and_provider_quote_use_real_http_routes(): void
    {
        $this->post('/register', ['name' => 'New Owner', 'email' => 'new@example.test', 'password' => 'SecurePassword123!', 'password_confirmation' => 'SecurePassword123!'])->assertRedirect('/dashboard');
        $u = User::where('email', 'new@example.test')->firstOrFail();
        $u->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($u);
        $this->post('/vehicles', ['make' => 'Toyota', 'model' => 'Vitz', 'year' => 2015, 'identifier' => 'KSP130-1234567', 'fuel_type' => 'Petrol', 'transmission' => 'CVT', 'mileage' => 45000, 'mileage_date' => today()->toDateString()])->assertRedirect();
        $v = $u->vehicles()->firstOrFail();
        $org = Organization::factory()->create();
        $staff = User::factory()->create();
        $org->members()->attach($staff);
        $this->post('/requests', ['vehicle_id' => $v->id, 'organization_id' => $org->id, 'kind' => 'service', 'title' => 'Oil and filters', 'notes' => 'Please itemize the work'])->assertRedirect('/app/requests');
        $req = ServiceRequest::firstOrFail();
        $this->actingAs($staff)->post('/requests/'.$req->id.'/quotes', ['organization_id' => $org->id, 'items' => [['description' => 'Labor', 'amount_minor' => 100000], ['description' => 'Filter and oil', 'amount_minor' => 350000]], 'duration' => '2 hours', 'valid_until' => today()->addWeek()->toDateString()])->assertRedirect();
        $this->actingAs($u)->post('/quotes/'.Quote::firstOrFail()->id.'/accept')->assertRedirect();
        $this->assertDatabaseHas('transactions', ['service_request_id' => $req->id, 'total_minor' => 450000]);
    }

    public function test_interval_reminder_is_calculated_from_actual_service_date_and_mileage(): void
    {
        $u = User::factory()->create();
        $v = Vehicle::factory()->create(['user_id' => $u->id]);
        $rec = ServiceRecord::create(['vehicle_id' => $v->id, 'author_id' => $u->id, 'service_date' => '2026-01-31', 'mileage' => 80000, 'work' => 'Oil service']);
        $this->actingAs($u)->post('/reminders', ['vehicle_id' => $v->id, 'title' => 'Oil', 'record_id' => $rec->id, 'interval_months' => 1, 'interval_km' => 5000])->assertRedirect();
        $r = Reminder::firstOrFail();
        $this->assertSame('2026-02-28', $r->due_date->toDateString());
        $this->assertSame(85000, $r->due_mileage);
        $this->assertStringContainsString('service record #'.$rec->id, $r->source);
    }

    public function test_reviewed_evidence_loses_review_label_after_a_correction(): void
    {
        Storage::fake('local');
        $u = User::factory()->create();
        $v = Vehicle::factory()->create(['user_id' => $u->id]);
        $rec = ServiceRecord::create(['vehicle_id' => $v->id, 'author_id' => $u->id, 'service_date' => today(), 'mileage' => 80000, 'work' => 'Oil service']);
        $a = Attachment::create(['user_id' => $u->id, 'service_record_id' => $rec->id, 'path' => 'evidence/invoice.pdf', 'name' => 'invoice.pdf']);
        Storage::disk('local')->put($a->path, 'Invoice');
        $mod = User::factory()->create(['role' => 'moderator']);
        $this->actingAs($mod)->get('/attachments/'.$a->id)->assertDownload();
        $this->post('/records/'.$rec->id.'/evidence-review', ['reason' => 'Reviewed invoice date and itemized work'])->assertRedirect();
        $this->assertSame('supported by reviewed evidence', $rec->fresh()->provenance);
        $this->actingAs($u)->post('/records/'.$rec->id.'/correct', ['work' => 'Corrected entry', 'reason' => 'Correction'])->assertRedirect();
        $this->assertSame('owner-submitted', $rec->fresh()->provenance);
        $this->assertNull($rec->fresh()->evidence_reviewed_at);
        $this->assertDatabaseCount('record_revisions', 2);
    }

    public function test_deletion_review_revokes_links_and_is_audited(): void
    {
        $u = User::factory()->create();
        $v = Vehicle::factory()->create(['user_id' => $u->id]);
        $s = HistoryShare::create(['vehicle_id' => $v->id, 'token_hash' => hash('sha256', 'test'), 'record_ids' => [], 'expires_at' => now()->addDay()]);
        $this->actingAs($u)->post('/account/deletion', ['password' => 'password'])->assertRedirect();
        $this->assertNotNull($s->fresh()->revoked_at);
        $this->assertDatabaseHas('deletion_requests', ['user_id' => $u->id, 'status' => 'requested']);
        $this->assertDatabaseHas('audit_events', ['action' => 'account.deletion_requested']);
    }

    public function test_vehicle_photo_is_private_and_upload_size_is_validated(): void
    {
        Storage::fake('local');
        $u = User::factory()->create();
        $v = Vehicle::factory()->create(['user_id' => $u->id, 'photo_path' => 'photos/test.jpg']);
        Storage::disk('local')->put('photos/test.jpg', 'test');
        $this->get('/photos/vehicle/'.$v->id)->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/photos/vehicle/'.$v->id)->assertForbidden();
        $this->actingAs($u)->get('/photos/vehicle/'.$v->id)->assertOk();
        $this->post('/records', ['vehicle_id' => $v->id, 'service_date' => today()->toDateString(), 'mileage' => 100, 'work' => 'Test', 'evidence' => UploadedFile::fake()->create('large.pdf', 6000, 'application/pdf')])->assertSessionHasErrors('evidence');
    }

    public function test_part_edits_invalidate_an_existing_fitment_and_other_supplier_cannot_edit(): void
    {
        $u = User::factory()->create();
        $org = Organization::factory()->create(['kind' => 'supplier']);
        $org->members()->attach($u);
        $v = Vehicle::factory()->create(['user_id' => $u->id]);
        $p = Part::create(['organization_id' => $org->id, 'title' => 'Filter', 'brand' => 'Denso', 'category' => 'Filters', 'description' => 'Original', 'oem_reference' => 'OEM-1', 'condition' => 'New', 'stock' => 'In stock']);
        Fitment::create(['part_id' => $p->id, 'vehicle_id' => $v->id, 'assessor_id' => $u->id, 'status' => 'confirmed', 'evidence' => 'Catalog', 'assessed_at' => now(), 'vehicle_fingerprint' => $v->fingerprint(), 'part_fingerprint' => $p->fingerprint()]);
        $this->actingAs($u)->put('/parts/'.$p->id, ['organization_id' => $org->id, 'title' => 'Filter', 'brand' => 'Denso', 'category' => 'Filters', 'description' => 'Changed specification', 'oem_reference' => 'OEM-2', 'condition' => 'New', 'stock' => 'In stock'])->assertRedirect();
        $this->assertNotSame($p->fresh()->fingerprint(), Fitment::firstOrFail()->part_fingerprint);
        $this->actingAs(User::factory()->create())->put('/parts/'.$p->id, ['organization_id' => $org->id, 'title' => 'Filter', 'brand' => 'Denso', 'category' => 'Filters', 'description' => 'Other', 'condition' => 'New', 'stock' => 'In stock'])->assertForbidden();
    }
}
