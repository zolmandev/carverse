<?php

namespace App\Http\Controllers;

use App\Actions\Workflow;
use App\Models\AuditEvent;
use App\Models\Complaint;
use App\Models\Organization;
use App\Models\Review;
use App\Policies\WorkspacePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProviderFeedbackController extends Controller
{
    private function authorizeProvider(Request $r, Organization $organization): void
    {
        abort_unless((new WorkspacePolicy)->admin($r->user()), 403);
        abort_unless($organization->status === 'approved', 404);
    }

    private function matchReview(Organization $organization, Review $review): void
    {
        abort_unless($review->transaction->organization_id === $organization->id, 404);
    }

    private function matchCase(Organization $organization, Complaint $complaint): void
    {
        abort_unless($complaint->transaction->organization_id === $organization->id, 404);
    }

    public function page(Request $r, Organization $organization): array
    {
        $this->authorizeProvider($r, $organization);
        $d = $r->validate(['feedback' => 'nullable|in:reviews,cases', 'feedback_status' => 'nullable|in:all,pending,approved,rejected']);
        $status = $d['feedback_status'] ?? 'all';
        $reviews = $organization->reviews()->with(['author:id,name', 'reviewer:id,name', 'transaction:id,organization_id,user_id,status'])->when($status !== 'all', fn ($q) => $q->where('moderation_status', $status))->orderByRaw("CASE WHEN moderation_status = 'pending' THEN 0 ELSE 1 END")->latest('reviews.created_at')->paginate(10, ['reviews.*'], 'reviews_page')->withQueryString()->through(fn ($review) => $review->makeVisible(['moderation_note', 'reviewed_by']));
        $cases = $organization->complaints()->with(['author:id,name', 'reviewer:id,name', 'attachments', 'transaction:id,organization_id,user_id,status'])->when($status !== 'all', fn ($q) => $q->where('moderation_status', $status))->orderByRaw("CASE WHEN moderation_status = 'pending' THEN 0 ELSE 1 END")->latest('complaints.created_at')->paginate(10, ['complaints.*'], 'cases_page')->withQueryString()->through(fn ($case) => $case->makeVisible(['moderation_note', 'reviewed_by', 'public_approved_by']));
        $events = AuditEvent::where(function ($q) use ($organization) {
            $q->where(fn ($q) => $q->where('subject_type', 'Review')->whereIn('subject_id', Review::whereHas('transaction', fn ($q) => $q->where('organization_id', $organization->id))->select('id')))->orWhere(fn ($q) => $q->where('subject_type', 'Complaint')->whereIn('subject_id', Complaint::whereHas('transaction', fn ($q) => $q->where('organization_id', $organization->id))->select('id')));
        })->latest()->limit(20)->get();

        return ['provider' => $organization->only('id', 'name', 'kind', 'status', 'logo_url'), 'section' => $d['feedback'] ?? 'reviews', 'filter' => $status, 'reviews' => $reviews, 'cases' => $cases, 'events' => $events];
    }

    public function review(Request $r, Organization $organization, Review $review)
    {
        $this->authorizeProvider($r, $organization);
        $this->matchReview($organization, $review);
        $d = $r->validate(['moderation_status' => 'required|in:pending,approved,rejected', 'moderation_note' => 'required|string|max:3000', 'content_checked' => 'accepted_if:moderation_status,approved']);
        DB::transaction(function () use ($r, $organization, $review, $d) {
            $locked = Review::with('transaction')->lockForUpdate()->findOrFail($review->id);
            $this->matchReview($organization, $locked);
            if ($d['moderation_status'] === 'approved' && ($locked->transaction->status !== 'completed' || $locked->transaction->user_id !== $locked->user_id)) {
                throw ValidationException::withMessages(['moderation_status' => 'Only a review by the owner of a completed transaction can be approved.']);
            }
            Workflow::audit('review.moderated', $locked, ['previous' => $locked->only('moderation_status', 'moderation_note', 'reviewed_by', 'reviewed_at', 'response', 'response_status'), 'decision' => $d]);
            $locked->update(collect($d)->except('content_checked')->all() + ['reviewed_by' => $r->user()->id, 'reviewed_at' => now(), 'response_status' => $d['moderation_status'] === 'approved' && $locked->response ? 'approved' : 'pending']);
        });

        return back()->with('message', 'Review decision saved. Only approved reviews contribute to public ratings.');
    }

    public function caseReview(Request $r, Organization $organization, Complaint $complaint)
    {
        $this->authorizeProvider($r, $organization);
        $this->matchCase($organization, $complaint);
        $d = $r->validate(['moderation_status' => 'required|in:pending,approved,rejected', 'moderation_note' => 'required|string|max:3000', 'evidence_checked' => 'accepted_if:moderation_status,approved']);
        DB::transaction(function () use ($r, $organization, $complaint, $d) {
            $case = Complaint::lockForUpdate()->findOrFail($complaint->id);
            $this->matchCase($organization, $case);
            if ($d['moderation_status'] === 'approved' && ! $case->attachments()->get()->contains(fn ($file) => Storage::disk('local')->exists($file->path))) {
                throw ValidationException::withMessages(['evidence_checked' => 'Available supporting evidence is required before accepting this case for review.']);
            }
            Workflow::audit('complaint.moderated', $case, ['previous' => $case->only('moderation_status', 'moderation_note', 'reviewed_by', 'reviewed_at', 'public_summary', 'public_approved_by', 'public_approved_at'), 'decision' => $d]);
            $update = collect($d)->except('evidence_checked')->all() + ['reviewed_by' => $r->user()->id, 'reviewed_at' => now()];
            if ($d['moderation_status'] !== 'approved') {
                $update += ['public_summary' => null, 'public_approved_by' => null, 'public_approved_at' => null];
            }$case->update($update);
        });

        return back()->with('message', 'Case moderation saved. Approving a case does not publish its allegation or resolve it.');
    }

    public function caseDecision(Request $r, Organization $organization, Complaint $complaint)
    {
        $this->authorizeProvider($r, $organization);
        $this->matchCase($organization, $complaint);
        $d = $r->validate(['status' => 'required|in:resolved,dismissed', 'decision' => 'required|string|max:5000']);
        DB::transaction(function () use ($organization, $complaint, $d) {
            $case = Complaint::lockForUpdate()->findOrFail($complaint->id);
            $this->matchCase($organization, $case);
            if ($case->moderation_status !== 'approved') {
                throw ValidationException::withMessages(['status' => 'Accept this case for review before recording a resolution.']);
            }Workflow::audit('complaint.decide', $case, ['previous' => $case->only('status', 'decision', 'public_summary', 'public_approved_by', 'public_approved_at'), 'changes' => $d]);
            $case->update($d + ['public_summary' => null, 'public_approved_by' => null, 'public_approved_at' => null]);
        });

        return back()->with('message', 'Case decision recorded. Any earlier public summary must be reviewed again.');
    }

    public function casePublication(Request $r, Organization $organization, Complaint $complaint)
    {
        $this->authorizeProvider($r, $organization);
        $this->matchCase($organization, $complaint);
        $d = $r->validate(['action' => 'required|in:publish,unpublish', 'public_summary' => 'nullable|required_if:action,publish|string|max:2000', 'redaction_confirmed' => 'accepted_if:action,publish', 'reason' => 'required|string|max:2000']);
        DB::transaction(function () use ($r, $organization, $complaint, $d) {
            $case = Complaint::lockForUpdate()->findOrFail($complaint->id);
            $this->matchCase($organization, $case);
            if ($d['action'] === 'publish' && ($case->moderation_status !== 'approved' || ! in_array($case->status, ['resolved', 'dismissed']))) {
                throw ValidationException::withMessages(['public_summary' => 'Only an accepted case with a recorded resolution can have a public summary.']);
            }Workflow::audit('complaint.'.$d['action'], $case, ['previous' => $case->only('public_summary', 'public_approved_by', 'public_approved_at'), 'reason' => $d['reason']]);
            $case->update(['public_summary' => $d['action'] === 'publish' ? $d['public_summary'] : null, 'public_approved_by' => $d['action'] === 'publish' ? $r->user()->id : null, 'public_approved_at' => $d['action'] === 'publish' ? now() : null]);
        });

        return back()->with('message',$d['action'] === 'publish' ? 'Redacted case summary approved for publication.' : 'Public case summary removed.');
    }
}
