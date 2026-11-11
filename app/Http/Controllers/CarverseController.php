<?php

namespace App\Http\Controllers;

use App\Actions\Workflow;
use App\Http\Requests\CommunityRequest;
use App\Http\Requests\VehicleRequest;
use App\Models\Article;
use App\Models\Attachment;
use App\Models\CarModel;
use App\Models\Community;
use App\Models\Complaint;
use App\Models\DeletionRequest;
use App\Models\Fitment;
use App\Models\HistoryShare;
use App\Models\Organization;
use App\Models\Part;
use App\Models\PaymentAttempt;
use App\Models\Quote;
use App\Models\RecordRevision;
use App\Models\Reminder;
use App\Models\Review;
use App\Models\ServiceRecord;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vehicle;
use App\Policies\WorkspacePolicy;
use App\Queries\WorkspaceQuery;
use App\Services\Payments\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CarverseController extends Controller
{
    public function __construct(private WorkspacePolicy $policy, private Workflow $workflow) {}

    private function allow(bool $allowed): void
    {
        abort_unless($allowed, 403);
    }

    public function index(Request $r, string $section = 'overview')
    {
        abort_if(in_array($section, ['requests', 'passport', 'reminders']) && ! config('carverse.legacy_workflows'), 404);

        if ($r->user()->role === 'admin') {
            if ($section !== 'admin') {
                return redirect('/app/admin');
            }

            return app(SuperUserController::class)->index($r);
        }

        return Inertia::render('carverse/workspace', app(WorkspaceQuery::class)->props($r, $section));
    }

    public function publicDirectory()
    {
        return Inertia::render('carverse/directory', ['providers' => app(WorkspaceQuery::class)->providers(), 'summaries' => Complaint::whereNotNull('public_summary')->get(['public_summary', 'updated_at'])]);
    }

    public function vehicle(VehicleRequest $r)
    {
        $v = $r->user()->vehicles()->create(collect($r->validated())->except('photo')->all());
        if ($r->hasFile('photo')) {
            $v->update(['photo_path' => $r->file('photo')->store('photos', 'local')]);
        }$r->session()->put('active_vehicle', $v->id);

        return back()->with('message', 'Vehicle added to your garage.');
    }

    public function updateVehicle(VehicleRequest $r, Vehicle $vehicle)
    {
        $this->allow($this->policy->vehicle($r->user(), $vehicle));
        $this->allow($r->integer('mileage') >= $vehicle->mileage);
        $vehicle->update(collect($r->validated())->except('photo')->all());
        if ($r->hasFile('photo')) {
            $vehicle->update(['photo_path' => $r->file('photo')->store('photos', 'local')]);
        }

        return back()->with('message', 'Vehicle updated. Fitment assessments are checked against the updated details.');
    }

    public function active(Request $r, Vehicle $vehicle)
    {
        $this->allow($this->policy->vehicle($r->user(), $vehicle));
        $r->session()->put('active_vehicle', $vehicle->id);

        return back();
    }

    public function requestQuote(Request $r)
    {
        $d = $r->validate(['vehicle_id' => 'required|exists:vehicles,id', 'organization_id' => 'nullable|exists:organizations,id', 'part_id' => 'nullable|exists:parts,id', 'kind' => 'required|in:service,part', 'title' => 'required|string|max:160', 'notes' => 'required|string|max:4000', 'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $this->allow($this->policy->vehicle($r->user(), Vehicle::findOrFail($d['vehicle_id'])));
        if (! empty($d['organization_id'])) {
            $this->allow(Organization::findOrFail($d['organization_id'])->status === 'approved');
        }if (! empty($d['part_id'])) {
            $p = Part::findOrFail($d['part_id']);
            $this->allow($p->organization->status === 'approved');
            $d['organization_id'] = $p->organization_id;
        }if (! empty($d['organization_id']) && Organization::findOrFail($d['organization_id'])->kind !== ($d['kind'] === 'part' ? 'supplier' : 'garage')) {
            throw ValidationException::withMessages(['organization_id' => 'Choose a parts supplier for a part request or a garage for a service request.']);
        }
        $req = ServiceRequest::create(collect($d)->except('evidence')->all() + ['user_id' => $r->user()->id]);
        $this->upload($r, ['service_request_id' => $req->id]);
        Workflow::audit('request.created', $req);

        return redirect('/app/requests')->with('message', 'Request sent. You will find provider quotes here.');
    }

    public function quote(Request $r, ServiceRequest $serviceRequest)
    {
        $d = $r->validate(['organization_id' => 'required|exists:organizations,id', 'items' => 'required|array|min:1|max:30', 'items.*.description' => 'required|string|max:250', 'items.*.amount_minor' => 'required|integer|min:1|max:100000000', 'valid_until' => 'required|date|after_or_equal:today', 'duration' => 'required|string|max:100']);
        $this->allow($r->user()->inOrganization((int) $d['organization_id']));
        $o = Organization::findOrFail($d['organization_id']);
        $this->allow(($serviceRequest->kind === 'part' && $o->kind === 'supplier') || ($serviceRequest->kind === 'service' && $o->kind === 'garage'));
        $this->allow(! $serviceRequest->organization_id || $serviceRequest->organization_id === $o->id);
        DB::transaction(function () use ($serviceRequest, $d) {
            $req = ServiceRequest::lockForUpdate()->findOrFail($serviceRequest->id);
            abort_unless(in_array($req->status, ['requested', 'quoted']), 422);
            $q = $req->quotes()->create([...$d, 'total_minor' => array_sum(array_column($d['items'], 'amount_minor'))]);
            $req->update(['status' => 'quoted']);
            Workflow::audit('quote.created', $q);
        });

        return back()->with('message', 'Quote sent.');
    }

    public function accept(Request $r, Quote $quote)
    {
        $this->workflow->accept($quote, $r->user());

        return back()->with('message', 'Quote accepted. Agreed terms have been saved.');
    }

    public function transition(Request $r, Transaction $transaction)
    {
        $d = $r->validate(['status' => 'required|string']);
        $this->workflow->transition($transaction, $d['status'], $r->user());

        return back()->with('message', 'Progress updated.');
    }

    public function pay(Request $r, Transaction $transaction, PaymentProvider $payments)
    {
        $this->allow($transaction->user_id === $r->user()->id);
        abort_if($transaction->payment_status === 'paid' || in_array($transaction->status, ['cancelled', 'disputed']), 422);
        $d = $r->validate(['phone' => ['required', 'regex:/^(?:\+?254|0)[17][0-9]{8}$/']]);
        Cache::lock('payment-init-'.$transaction->id, 40)->block(3, function () use ($transaction, $payments, $d) {
            $locked = Transaction::findOrFail($transaction->id);
            abort_unless($locked->payment_status !== 'paid', 422);
            if (PaymentAttempt::where('transaction_id', $locked->id)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['payment' => 'A payment attempt is already pending. Wait for verification before retrying.']);
            }$payments->initiate($locked, $d['phone']);
        });

        return back()->with('message', config('payments.driver') === 'demo' ? 'Demo payment recorded. No money was moved.' : 'Sandbox payment request sent. Payment is pending provider verification.');
    }

    public function record(Request $r)
    {
        $d = $r->validate(['vehicle_id' => 'required|exists:vehicles,id', 'service_date' => 'required|date|before_or_equal:today', 'mileage' => 'required|integer|min:0|max:3000000', 'work' => 'required|string|max:4000', 'parts_used' => 'nullable|string|max:2000', 'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $this->allow($this->policy->vehicle($r->user(), Vehicle::findOrFail($d['vehicle_id'])));
        $rec = ServiceRecord::create(collect($d)->except('evidence')->all() + ['author_id' => $r->user()->id, 'provenance' => 'owner-submitted']);
        $this->upload($r, ['service_record_id' => $rec->id]);
        Workflow::audit('record.published', $rec);

        return back()->with('message', 'Service entry saved with its source.');
    }

    public function correct(Request $r, ServiceRecord $record)
    {
        $d = $r->validate(['work' => 'required|string|max:4000', 'reason' => 'required|string|max:1000', 'service_date' => 'sometimes|required|date|before_or_equal:today', 'mileage' => 'sometimes|required|integer|min:0|max:3000000', 'parts_used' => 'sometimes|nullable|string|max:2000', 'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $this->workflow->correct($record, collect($d)->except('reason', 'evidence')->all(), $d['reason'], $r->user());
        $this->upload($r, ['service_record_id' => $record->id]);

        return back()->with('message', 'Correction saved. Previous version preserved.');
    }

    public function reminder(Request $r)
    {
        $d = $r->validate(['vehicle_id' => 'required|exists:vehicles,id', 'title' => 'required|string|max:120', 'due_date' => 'nullable|date', 'due_mileage' => 'nullable|integer|min:0|max:3000000', 'record_id' => 'nullable|exists:service_records,id', 'interval_months' => 'nullable|integer|between:1,120', 'interval_km' => 'nullable|integer|between:100,200000']);
        $this->allow($this->policy->vehicle($r->user(), Vehicle::findOrFail($d['vehicle_id'])));
        $source = 'Owner-defined date or mileage threshold';
        if (! empty($d['record_id'])) {
            $rec = ServiceRecord::findOrFail($d['record_id']);
            $this->allow($rec->vehicle_id === (int) $d['vehicle_id']);
            if (! empty($d['interval_months'])) {
                $d['due_date'] = $rec->service_date->copy()->addMonthsNoOverflow((int) $d['interval_months'])->toDateString();
            }if (! empty($d['interval_km'])) {
                $d['due_mileage'] = $rec->mileage + (int) $d['interval_km'];
            }$source = 'Owner-defined interval from service record #'.$rec->id.' ('.$rec->service_date->toDateString().', '.$rec->mileage.' km). No manufacturer guidance implied.';
        }
        if (empty($d['due_date']) && empty($d['due_mileage'])) {
            throw ValidationException::withMessages(['reminder' => 'Set a due date / mileage, or choose a service record and interval.']);
        }
        Reminder::create(collect($d)->only('vehicle_id', 'title', 'due_date', 'due_mileage')->all() + ['source' => $source]);

        return back()->with('message', 'Maintenance reminder saved.');
    }

    public function review(Request $r, Transaction $transaction)
    {
        $this->allow($transaction->user_id === $r->user()->id);
        abort_unless($transaction->status === 'completed' && ! $transaction->review, 422);
        $d = $r->validate(['quality' => 'required|integer|between:1,5', 'communication' => 'required|integer|between:1,5', 'timeliness' => 'required|integer|between:1,5', 'value' => 'required|integer|between:1,5', 'body' => 'required|string|max:2000']);
        Review::create($d + ['transaction_id' => $transaction->id, 'user_id' => $r->user()->id]);

        return back()->with('message', 'Your transaction-linked review is published.');
    }

    public function respondReview(Request $r, Review $review)
    {
        $this->allow($r->user()->inOrganization($review->transaction->organization_id));
        $review->update($r->validate(['response' => 'required|string|max:2000']));

        return back();
    }

    public function complain(Request $r, Transaction $transaction)
    {
        $this->allow($transaction->user_id === $r->user()->id);
        $d = $r->validate(['body' => 'required|string|max:5000', 'evidence' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $c = Complaint::create(['transaction_id' => $transaction->id, 'user_id' => $r->user()->id, 'body' => $d['body']]);
        $this->upload($r, ['complaint_id' => $c->id]);
        Workflow::audit('complaint.opened', $c);

        return back()->with('message', 'Complaint submitted privately for review.');
    }

    public function complaintAction(Request $r, Complaint $complaint)
    {
        $action = $r->validate(['action' => 'required|in:respond,decide,appeal,publish'])['action'];
        switch ($action) {
            case 'respond':$this->allow($r->user()->inOrganization($complaint->transaction->organization_id));
                $d = $r->validate(['provider_response' => 'required|string|max:5000']);
                break;
            case 'appeal':$this->allow($complaint->user_id === $r->user()->id || $r->user()->inOrganization($complaint->transaction->organization_id));
                abort_unless(in_array($complaint->status, ['resolved', 'dismissed']), 422);
                $d = $r->validate(['appeal' => 'required|string|max:5000']);
                $d['status'] = 'appealed';
                break;
            case 'decide':$this->allow($this->policy->moderate($r->user()));
                $d = $r->validate(['decision' => 'required|string|max:5000', 'status' => 'required|in:resolved,dismissed']);
                break;
            default:$this->allow($this->policy->moderate($r->user()));
                abort_unless(in_array($complaint->status, ['resolved', 'dismissed']), 422);
                $d = $r->validate(['public_summary' => 'required|string|max:2000']);
        }DB::transaction(function () use ($complaint, $d, $action) {
            Workflow::audit('complaint.'.$action, $complaint, ['previous' => $complaint->only('status', 'decision', 'appeal', 'public_summary'), 'changes' => $d]);
            $complaint->update($d);
        });

        return back()->with('message', 'Complaint updated.');
    }

    private function upload(Request $r, array $link): void
    {
        if ($r->hasFile('evidence')) {
            $f = $r->file('evidence');
            Attachment::create($link + ['user_id' => $r->user()->id, 'path' => $f->store('evidence', 'local'), 'name' => basename($f->getClientOriginalName())]);
        }
    }

    public function download(Request $r, Attachment $attachment)
    {
        $this->allow($this->policy->attachment($r->user(), $attachment));

        return Storage::disk('local')->download($attachment->path, $attachment->name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function share(Request $r, Vehicle $vehicle)
    {
        $this->allow($this->policy->vehicle($r->user(), $vehicle));
        $d = $r->validate(['record_ids' => 'required|array|min:1', 'record_ids.*' => 'integer', 'days' => 'required|integer|between:1,30']);
        $this->allow($vehicle->records()->whereIn('id', $d['record_ids'])->count() === count(array_unique($d['record_ids'])));
        $token = Str::random(64);
        HistoryShare::create(['vehicle_id' => $vehicle->id, 'token_hash' => hash('sha256', $token), 'record_ids' => $d['record_ids'], 'expires_at' => now()->addDays($d['days'])]);

        return back()->with('share_url', url('/history/'.$token))->with('message', 'Read-only link created. Save it now; it will not be shown again.');
    }

    public function revoke(Request $r, HistoryShare $share)
    {
        $this->allow($this->policy->vehicle($r->user(), $share->vehicle));
        $share->update(['revoked_at' => now()]);

        return back();
    }

    public function shared(string $token)
    {
        $s = HistoryShare::where('token_hash', hash('sha256', $token))->whereNull('revoked_at')->where('expires_at', '>', now())->firstOrFail();

        return Inertia::render('carverse/shared', ['vehicle' => $s->vehicle->only('make', 'model', 'year'), 'records' => $s->vehicle->records()->whereIn('id', $s->record_ids)->get(['service_date', 'mileage', 'work', 'parts_used', 'provenance', 'version', 'provider_name']), 'expires' => $s->expires_at->toIso8601String()]);
    }

    public function apply(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:160', 'kind' => 'required|in:garage,supplier', 'location' => 'required|string|max:160', 'phone' => ['required', 'regex:/^(?:\+?254|0)[17][0-9]{8}$/'], 'description' => 'required|string|max:2000', 'specialties' => 'nullable|array|max:20', 'specialties.*' => 'nullable|string|max:100', 'opening_hours' => 'required|string|max:200', 'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', 'evidence' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $o = Organization::create(collect($d)->except('evidence', 'photo')->all());
        if ($r->hasFile('photo')) {
            $o->update(['photo_path' => $r->file('photo')->store('photos', 'local')]);
        }$o->members()->attach($r->user()->id);
        $this->upload($r, ['organization_id' => $o->id]);
        Workflow::audit('provider.applied', $o);

        return back()->with('message', 'Application submitted. Staff access starts after approval.');
    }

    public function approve(Request $r, Organization $organization)
    {
        $this->allow($this->policy->admin($r->user()));
        $d = $r->validate(['status' => 'required|in:approved,rejected,pending', 'verification_scope' => 'required|string|max:2000']);
        $organization->update($d + ['verified_at' => $d['status'] === 'approved' ? now() : null]);
        Workflow::audit('provider.reviewed', $organization, $d);

        return back();
    }

    public function part(Request $r, ?Part $part = null)
    {
        $d = $r->validate(['organization_id' => 'required|exists:organizations,id', 'title' => 'required|string|max:160', 'brand' => 'required|string|max:80', 'category' => 'required|string|max:80', 'description' => 'required|string|max:3000', 'oem_reference' => 'nullable|string|max:120', 'condition' => 'required|in:New,Used,Reconditioned', 'price_minor' => 'nullable|integer|min:1|max:100000000', 'stock' => 'required|in:In stock,On request,Out of stock', 'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120']);
        $this->allow($r->user()->inOrganization((int) $d['organization_id']) && Organization::findOrFail($d['organization_id'])->kind === 'supplier');
        if ($part && $part->exists) {
            $this->allow($part->organization_id === (int) $d['organization_id']);
        }$p = $part && $part->exists ? $part : new Part;
        $p->fill(collect($d)->except('photo')->all())->save();
        if ($r->hasFile('photo')) {
            $p->update(['photo_path' => $r->file('photo')->store('photos', 'local')]);
        }

        return back()->with('message', 'Part added. Compatibility starts as unknown.');
    }

    public function fitment(Request $r, Part $part)
    {
        $d = $r->validate(['vehicle_id' => 'required|exists:vehicles,id', 'status' => 'required|in:confirmed,potential,unknown,incompatible', 'evidence' => 'required|string|max:3000']);
        $this->allow($r->user()->inOrganization($part->organization_id) || $this->policy->admin($r->user()));
        $v = Vehicle::findOrFail($d['vehicle_id']);
        $this->allow($v->user_id === $r->user()->id || ServiceRequest::where('vehicle_id', $v->id)->where('organization_id', $part->organization_id)->exists() || $this->policy->admin($r->user()));
        Fitment::updateOrCreate(['part_id' => $part->id, 'vehicle_id' => $v->id], $d + ['assessor_id' => $r->user()->id, 'vehicle_fingerprint' => $v->fingerprint(), 'part_fingerprint' => $part->fingerprint(), 'assessed_at' => now(), 'vehicle_details' => $v->only('make', 'model', 'year', 'variant', 'engine_code', 'fuel_type', 'transmission'), 'part_details' => $part->only('title', 'brand', 'oem_reference', 'condition')]);
        Workflow::audit('fitment.assessed', $part, $d);

        return back();
    }

    public function content(Request $r, ?Article $article = null)
    {
        $this->allow($this->policy->admin($r->user()));
        $d = $r->validate(['title' => 'required|string|max:200', 'author' => 'required|string|max:120', 'body' => 'required|string|max:20000', 'sources' => 'required|string|max:4000', 'video_url' => ['nullable', 'url', 'max:250', 'regex:~^https://(?:www\.)?(?:youtube\.com/watch\?v=[A-Za-z0-9_-]+|youtu\.be/[A-Za-z0-9_-]+|vimeo\.com/[0-9]+)$~'], 'status' => 'required|in:draft,published', 'reviewed_at' => 'required|date|before_or_equal:today']);
        $a = $article && $article->exists ? $article : new Article;
        $a->fill($d + ['published_at' => $d['status'] === 'published' ? today() : null])->save();
        Workflow::audit('article.created', $a);

        return back();
    }

    public function publish(Request $r, Article $article)
    {
        $this->allow($this->policy->admin($r->user()));
        $article->update($r->validate(['status' => 'required|in:draft,published']) + ['published_at' => today()]);
        Workflow::audit('article.status', $article);

        return back();
    }

    public function community(CommunityRequest $r, ?Community $community = null)
    {
        $this->allow($this->policy->admin($r->user()));
        $d = $r->validated();
        $selectedModel = CarModel::with('brand')->findOrFail($d['car_model_id']);
        $d['model'] = $selectedModel->brand->name.' '.$selectedModel->name;

        $c = $community && $community->exists ? $community : new Community;
        $previous = $c->exists ? $c->only('name', 'model', 'url', 'car_brand_id', 'car_model_id', 'car_trim_id') : null;
        DB::transaction(function () use ($c, $d, $previous) {
            $c->fill($d)->save();
            Workflow::audit($previous ? 'community.updated' : 'community.created', $c, ['previous' => $previous]);
        });

        return back();
    }

    public function finance(Request $r, Transaction $transaction)
    {
        $this->allow($this->policy->admin($r->user()));
        $d = $r->validate(['commission_minor' => 'required|integer|min:0', 'variable_cost_minor' => 'nullable|integer|min:0', 'fitment_return' => 'required|boolean']);
        $transaction->update($d);
        Workflow::audit('finance.recorded', $transaction, $d);

        return back();
    }

    public function preferences(Request $r)
    {
        $d = $r->validate(['phone' => ['nullable', 'regex:/^(?:\+?254|0)[17][0-9]{8}$/'], 'location' => 'required|string|max:160', 'reminder_notifications' => 'required|boolean']);
        $r->user()->update($d);

        return back()->with('message', 'Preferences updated.');
    }

    public function photo(Request $r, string $type, int $id)
    {
        $model = match ($type) {
            'vehicle' => Vehicle::findOrFail($id),'part' => Part::findOrFail($id),'provider' => Organization::findOrFail($id),default => abort(404)
        };
        if ($type === 'vehicle') {
            $this->allow($r->user() && $this->policy->vehicle($r->user(), $model));
        } else {
            $this->allow(($type === 'part' ? $model->organization->status : $model->status) === 'approved');
        }abort_unless($model->photo_path, 404);

        return response()->file(Storage::disk('local')->path($model->photo_path), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function reviewEvidence(Request $r, ServiceRecord $record)
    {
        $this->allow($this->policy->moderate($r->user()));
        $d = $r->validate(['reason' => 'required|string|max:2000']);
        abort_unless($record->attachments()->exists(), 422, 'An evidence attachment is required.');
        DB::transaction(function () use ($record, $r, $d) {
            RecordRevision::create(['service_record_id' => $record->id, 'actor_id' => $r->user()->id, 'previous' => $record->getAttributes(), 'reason' => 'Evidence review: '.$d['reason']]);
            $record->update(['provenance' => 'supported by reviewed evidence', 'evidence_reviewed_at' => now(), 'version' => $record->version + 1]);
            Workflow::audit('record.evidence_reviewed', $record, $d);
        });

        return back();
    }

    public function deletion(Request $r)
    {
        $r->validate(['password' => 'required|current_password']);
        $request = DeletionRequest::firstOrCreate(['user_id' => $r->user()->id, 'status' => 'requested']);
        Workflow::audit('account.deletion_requested', $request);
        HistoryShare::whereIn('vehicle_id', $r->user()->vehicles()->pluck('id'))->update(['revoked_at' => now()]);

        return back()->with('message', 'Deletion review requested. Your sharing links have been revoked. The pilot administrator will document retention and follow up.');
    }

    public function reviewDeletion(Request $r, DeletionRequest $deletionRequest)
    {
        $this->allow($this->policy->admin($r->user()));
        $d = $r->validate(['status' => 'required|in:requested,retention_review,closed', 'retention_note' => 'required|string|max:4000']);
        Workflow::audit('account.retention_review', $deletionRequest, ['previous' => $deletionRequest->only('status', 'retention_note'), 'changes' => $d]);
        $deletionRequest->update($d);

        return back();
    }

    public function membership(Request $r, Organization $organization)
    {
        $this->allow($this->policy->admin($r->user()));
        $d = $r->validate(['email' => 'required|email|exists:users,email']);
        $member = User::where('email', $d['email'])->firstOrFail();
        $organization->members()->syncWithoutDetaching([$member->id]);
        Workflow::audit('organization.member_added', $organization, ['user_id' => $member->id]);

        return back();
    }

    public function role(Request $r, User $user)
    {
        $this->allow($this->policy->admin($r->user()));
        $d = $r->validate(['role' => 'required|in:owner,moderator,admin']);
        abort_if($user->id === $r->user()->id && $d['role'] !== 'admin', 422, 'Ask another administrator to change your role.');
        Workflow::audit('user.role_changed', $user, ['from' => $user->role, 'to' => $d['role']]);
        $user->forceFill($d)->save();

        return back();
    }

    public function cancelRequest(Request $r, ServiceRequest $serviceRequest)
    {
        $this->allow($serviceRequest->user_id === $r->user()->id);
        DB::transaction(function () use ($serviceRequest) {
            $req = ServiceRequest::lockForUpdate()->findOrFail($serviceRequest->id);
            abort_unless(in_array($req->status, ['requested', 'quoted']), 422);
            $before = $req->status;
            $req->update(['status' => 'cancelled']);
            Workflow::audit('request.cancelled', $req, ['from' => $before, 'to' => 'cancelled']);
        });

        return back();
    }

    public function removeCommunity(Request $r, Community $community)
    {
        $this->allow($this->policy->admin($r->user()));
        Workflow::audit('community.removed', $community, ['previous' => $community->only('name', 'model', 'url')]);
        $community->delete();

        return back();
    }

    public function export(Request $r)
    {
        $u = $r->user();

        return response()->streamDownload(function () use ($u) {
            echo json_encode(['profile' => $u->only('name', 'email', 'phone', 'location'), 'vehicles' => $u->vehicles, 'requests' => ServiceRequest::with('quotes', 'transaction')->where('user_id', $u->id)->get(), 'records' => ServiceRecord::with('revisions')->whereIn('vehicle_id', $u->vehicles()->pluck('id'))->get(), 'reviews' => Review::where('user_id', $u->id)->get(), 'complaints' => Complaint::where('user_id', $u->id)->get()], JSON_PRETTY_PRINT);
        }, 'carverse-export.json', ['Content-Type' => 'application/json']);
    }
}
