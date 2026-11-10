<?php

namespace Database\Seeders;

use App\Actions\Workflow;
use App\Models\Article;
use App\Models\Attachment;
use App\Models\AuditEvent;
use App\Models\Complaint;
use App\Models\Fitment;
use App\Models\Organization;
use App\Models\Part;
use App\Models\Quote;
use App\Models\Reminder;
use App\Models\Review;
use App\Models\ServiceRecord;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Fictional demo seeding is forbidden in production.');
        }
        $password = Hash::make('CarverseDemo!2026');
        $owner = User::firstOrCreate(['email' => 'owner@carverse.test'], ['name' => 'Amani Wanjiru (Demo)', 'password' => $password, 'email_verified_at' => now(), 'phone' => '+254700000001']);
        $owner->forceFill(['role' => 'admin'])->save();
        $admin = User::firstOrCreate(['email' => 'admin@carverse.test'], ['name' => 'Pilot Administrator (Demo)', 'password' => $password, 'email_verified_at' => now(), 'role' => 'admin']);
        User::firstOrCreate(['email' => 'moderator@carverse.test'], ['name' => 'Pilot Moderator (Demo)', 'password' => $password, 'email_verified_at' => now(), 'role' => 'moderator']);
        $second = User::firstOrCreate(['email' => 'owner2@carverse.test'], ['name' => 'Baraka Otieno (Demo)', 'password' => $password, 'email_verified_at' => now()]);
        $v = Vehicle::firstOrCreate(['user_id' => $owner->id, 'registration' => 'KDE 000D'], ['make' => 'Toyota', 'model' => 'Fielder', 'year' => 2016, 'variant' => '1.5 Hybrid', 'engine_code' => '1NZ-FXE', 'fuel_type' => 'Hybrid', 'transmission' => 'CVT', 'identifier' => 'NKE165-0000000', 'mileage' => 87450, 'mileage_date' => today()->subDays(12)]);
        Vehicle::firstOrCreate(['user_id' => $owner->id, 'registration' => 'KCF 000D'], ['make' => 'Subaru', 'model' => 'Forester', 'year' => 2014, 'variant' => '2.0 XT', 'engine_code' => 'FA20', 'fuel_type' => 'Petrol', 'transmission' => 'CVT', 'identifier' => 'SJG-000000', 'mileage' => 118200, 'mileage_date' => today()->subDays(55)]);
        Vehicle::firstOrCreate(['user_id' => $second->id, 'registration' => 'KDG 000D'], ['make' => 'Mazda', 'model' => 'Demio', 'year' => 2017, 'fuel_type' => 'Petrol', 'transmission' => 'Automatic', 'mileage' => 63000, 'mileage_date' => today()]);
        $names = ['Redline Auto Care', 'Karibu Hybrid Workshop', 'Ridgeway Subaru Studio', 'Eastlands Motor Works', 'Mji Auto Specialists', 'Junction Parts Supply', 'Metro OEM Spares', 'Pamoja Auto Parts'];
        $locations = ['Industrial Area', 'Ngong Road', 'Ridgeways', 'Donholm', 'Westlands', 'Kirinyaga Road', 'Mombasa Road', 'Parklands'];
        $orgs = [];
        foreach ($names as $i => $name) {
            $kind = $i < 5 ? 'garage' : 'supplier';
            $o = Organization::firstOrCreate(['name' => $name.' (Fictional)'], ['kind' => $kind, 'location' => $locations[$i].', Nairobi', 'phone' => '+25470000000'.($i + 1), 'description' => 'Fictional Nairobi pilot provider for testing vehicle care workflows. No real business endorsement is implied.', 'status' => 'approved', 'verification_scope' => 'DEMO: simulated business identity and contact review only. No real-world verification performed.', 'verified_at' => now()->subDays(7), 'specialties' => $i < 5 ? ['Toyota', 'Scheduled maintenance', 'Diagnostics'] : ['Filters', 'Brakes', 'Service parts'], 'opening_hours' => 'Mon–Sat · 8:00–18:00']);
            $orgs[] = $o;
            $u = User::firstOrCreate(['email' => ($i < 5 ? 'garage' : 'supplier').($i < 5 ? $i + 1 : $i - 4).'@carverse.test'], ['name' => $name.' Staff (Demo)', 'password' => $password, 'email_verified_at' => now()]);
            $o->members()->syncWithoutDetaching([$u->id]);
        }
        $parts = [['Oil filter', 'Denso', 'Filters', '90915-YZZE1', 145000, 'confirmed'], ['Front brake pads', 'Akebono', 'Brakes', 'AN-742WK', 680000, 'potential'], ['Air filter', 'Sakura', 'Filters', 'A-3302', 210000, 'unknown'], ['Front brake disc', 'Brembo', 'Brakes', '09-DEMO', 920000, 'incompatible'], ['Cabin air filter', 'Bosch', 'Filters', '0986-DEMO', null, 'unknown'], ['Iridium spark plug set', 'NGK', 'Ignition', 'ILK-DEMO', 840000, 'potential']];
        foreach ($parts as $i => [$title,$brand,$category,$ref,$price,$status]) {
            $p = Part::firstOrCreate(['title' => $title, 'organization_id' => $orgs[5 + $i % 3]->id], ['brand' => $brand, 'category' => $category, 'description' => 'Fictional pilot listing. Confirm your engine and chassis details with the supplier before ordering.', 'oem_reference' => $ref, 'condition' => 'New', 'price_minor' => $price, 'stock' => $price ? 'In stock' : 'On request']);
            if ($status !== 'unknown') {
                Fitment::firstOrCreate(['part_id' => $p->id, 'vehicle_id' => $v->id], ['status' => $status, 'evidence' => 'FICTIONAL assessment reference DEMO-'.($i + 1).': 2016 Fielder NKE165, 1NZ-FXE. Training example, not a real fitment source.', 'assessor_id' => $admin->id, 'assessed_at' => now(), 'vehicle_fingerprint' => $v->fingerprint(), 'part_fingerprint' => $p->fingerprint(), 'vehicle_details' => $v->only('make', 'model', 'year', 'variant', 'engine_code', 'fuel_type', 'transmission'), 'part_details' => $p->only('title', 'brand', 'oem_reference', 'condition')]);
            }
        }
        foreach (['Oil & filter service', 'Front brake inspection', 'Hybrid system health check', 'Replacement cabin filter'] as $i => $title) {
            $req = ServiceRequest::firstOrCreate(['user_id' => $owner->id, 'title' => $title], ['vehicle_id' => $v->id, 'organization_id' => $orgs[$i]->id, 'kind' => 'service', 'notes' => 'Fictional pilot request. Please quote separately for labor and parts.', 'status' => $i === 3 ? 'requested' : 'quoted']);
            if ($i === 3) {
                continue;
            }$q = Quote::firstOrCreate(['service_request_id' => $req->id, 'organization_id' => $orgs[$i]->id], ['items' => [['description' => 'Inspection and labor', 'amount_minor' => 250000], ['description' => 'Service parts', 'amount_minor' => 420000]], 'total_minor' => 670000, 'valid_until' => today()->addDays(14), 'duration' => 'Half a day']);
            if ($i < 2 && ! $req->transaction) {
                $t = app(Workflow::class)->accept($q, $owner);
                if ($i === 0) {
                    $t->update(['status' => 'completed', 'payment_status' => 'paid', 'completed_at' => now()->subDays(10), 'commission_minor' => 33500, 'variable_cost_minor' => 12000]);
                    $req->update(['status' => 'completed']);
                    ServiceRecord::firstOrCreate(['transaction_id' => $t->id], ['vehicle_id' => $v->id, 'author_id' => $owner->id, 'service_date' => today()->subDays(10), 'mileage' => 87000, 'work' => 'Engine oil and filter replaced; fluid levels inspected', 'parts_used' => 'Demo oil filter and engine oil', 'provenance' => 'provider-submitted', 'provider_name' => $orgs[$i]->name]);
                    Review::firstOrCreate(['transaction_id' => $t->id], ['user_id' => $owner->id, 'quality' => 5, 'communication' => 4, 'timeliness' => 4, 'value' => 5, 'body' => 'Fictional review: the agreed work was explained clearly.', 'response' => 'Fictional response: thank you for your feedback.']);
                    $c = Complaint::firstOrCreate(['transaction_id' => $t->id, 'user_id' => $owner->id], ['body' => 'Fictional complaint: invoice copy was delayed.', 'provider_response' => 'Fictional response: invoice provided after follow-up.', 'status' => 'resolved', 'decision' => 'Demo moderator reviewed correspondence; documentation issue resolved.']);
                    AuditEvent::firstOrCreate(['action' => 'complaint.decide', 'subject_type' => 'Complaint', 'subject_id' => $c->id], ['actor_id' => $admin->id, 'details' => ['note' => 'Fictional moderation training example']]);
                }
            }
        }

        $part = Part::firstOrFail();
        $pr = ServiceRequest::firstOrCreate(['user_id' => $owner->id, 'title' => 'Oil filter replacement part'], ['vehicle_id' => $v->id, 'organization_id' => $part->organization_id, 'part_id' => $part->id, 'kind' => 'part', 'notes' => 'Fictional order: confirm chassis and engine reference before dispatch.', 'status' => 'quoted']);
        $pq = Quote::firstOrCreate(['service_request_id' => $pr->id, 'organization_id' => $part->organization_id], ['items' => [['description' => 'Oil filter, fictional listing', 'amount_minor' => 145000]], 'total_minor' => 145000, 'valid_until' => today()->addDays(14), 'duration' => 'Next business day']);
        if (! $pr->transaction) {
            $pt = app(Workflow::class)->accept($pq, $owner);
            $pt->update(['status' => 'in_progress']);
            $pr->update(['status' => 'in_progress']);
        }
        $record = ServiceRecord::where('vehicle_id', $v->id)->first();
        if ($record) {
            $path = 'demo/fictional-invoice-notes.txt';
            Storage::disk('local')->put($path, 'FICTIONAL DEMO EVIDENCE. This is a training fixture, not a real invoice. Oil and filter service, recorded mileage 87,000 km.');
            Attachment::firstOrCreate(['service_record_id' => $record->id, 'path' => $path], ['user_id' => $owner->id, 'name' => 'Fictional invoice notes.txt']);
        }
        $complaint = Complaint::where('user_id', $owner->id)->first();
        if ($complaint) {
            $path = 'demo/fictional-complaint-notes.txt';
            Storage::disk('local')->put($path, 'FICTIONAL DEMO EVIDENCE. Training correspondence: the invoice was requested after the demo service and later provided. No real allegation is made.');
            Attachment::firstOrCreate(['complaint_id' => $complaint->id, 'path' => $path], ['user_id' => $owner->id, 'name' => 'Fictional complaint correspondence.txt']);
        }
        Reminder::firstOrCreate(['vehicle_id' => $v->id, 'title' => 'Oil and filters'], ['due_date' => today()->addMonths(5), 'due_mileage' => 92000, 'source' => 'Owner-defined: 6 months or 5,000 km from fictional last service at 87,000 km. Not manufacturer guidance.']);
        Reminder::firstOrCreate(['vehicle_id' => $v->id, 'title' => 'Check tire pressures'], ['due_date' => today()->subDay(), 'source' => 'Owner-defined date; demonstration of scheduled reminders.']);
        Article::firstOrCreate(['title' => 'A better record starts with your next service'], ['author' => 'Carverse demo editorial team', 'body' => 'FICTIONAL DEMO CONTENT\n\nRecord the service date, odometer reading, work performed, and the source of each invoice. Ask your specialist which maintenance schedule applies to your engine and operating conditions.\n\nA service history is useful evidence, but it does not prove the vehicle has no other issues.', 'sources' => 'Demo editorial example. Consult the vehicle manufacturer’s service handbook for model-specific guidance.', 'status' => 'published', 'published_at' => today(), 'reviewed_at' => today()]);
    }
}
