<?php

namespace App\Http\Controllers;

use App\Actions\Workflow;
use App\Http\Requests\EventSubmissionRequest;
use App\Http\Requests\AdminEventUpdateRequest;
use App\Models\MotoringEvent;
use App\Policies\EventPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EventController extends Controller
{
    public function __construct(private EventPolicy $policy) {}

    private function admin(Request $r): void
    {
        abort_unless($this->policy->review($r->user()), 403);
    }

    public function ownerData(Request $r): array
    {
        $d = $r->validate(['event_search' => 'nullable|string|max:160', 'event_category' => 'nullable|in:meetup,car_show,track_day,motorsport,workshop,other']);
        $user = $r->user();

        return ['upcoming' => MotoringEvent::published()->where('ends_at', '>=', now())->when($d['event_category'] ?? null, fn ($q, $category) => $q->where('category', $category))->when($d['event_search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($search).'%'])->orWhereRaw('LOWER(location) LIKE ?', ['%'.mb_strtolower($search).'%'])))->orderBy('starts_at')->paginate(12, ['id', 'title', 'category', 'description', 'organizer_name', 'venue', 'location', 'starts_at', 'ends_at', 'website_url', 'poster_path', 'status', 'version', 'approval_scope'], 'events_page')->withQueryString(),
            'mine' => $user ? MotoringEvent::where('user_id', $user->id)->latest()->paginate(10, ['*'], 'my_events_page')->withQueryString()->through(fn ($event) => $event->makeVisible(['contact_email', 'contact_phone', 'evidence_name', 'review_note'])) : null, 'canSubmit' => $user && $user->hasVerifiedPhone(), 'defaultEmail' => $user?->email, 'filters' => $d];
    }

    public function publicIndex(Request $r)
    {
        return Inertia::render('carverse/events', ['data' => $this->ownerData($r), 'flash' => ['message' => session('message')]]);
    }

    public function submitPage(Request $r)
    {
        return Inertia::render('carverse/event-submission', ['email' => $r->user()->email, 'event' => null]);
    }

    public function minePage(Request $r)
    {
        $d = $r->validate(['status' => 'nullable|in:pending,approved,rejected,withdrawn']);
        $mine = MotoringEvent::where('user_id', $r->user()->id);

        return Inertia::render('carverse/my-events', [
            'events' => (clone $mine)->when($d['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->latest()->paginate(9)->withQueryString()
                ->through(fn ($event) => $event->makeVisible(['evidence_name', 'review_note'])),
            'counts' => (clone $mine)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
            'status' => $d['status'] ?? 'all',
            'flash' => ['message' => session('message')],
        ]);
    }

    public function editPage(Request $r, MotoringEvent $event)
    {
        abort_unless($this->policy->update($r->user(), $event), 403);

        return Inertia::render('carverse/event-submission', [
            'email' => $r->user()->email,
            'event' => $event->makeVisible(['contact_email', 'contact_phone', 'evidence_name', 'review_note']),
        ]);
    }

    public function adminData(Request $r): array
    {
        $this->admin($r);
        $d = $r->validate(['event_status' => 'nullable|in:pending,approved,rejected,withdrawn', 'q' => 'nullable|string|max:160']);
        $status = $d['event_status'] ?? 'pending';
        $events = MotoringEvent::with(['author:id,name,email', 'reviewer:id,name'])->where('status', $status)->when($d['q'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($search).'%'])->orWhereRaw('LOWER(location) LIKE ?', ['%'.mb_strtolower($search).'%'])))->orderBy('starts_at')->paginate(15, ['*'], 'event_applications_page')->withQueryString()->through(fn ($event) => $event->makeVisible(['user_id', 'contact_email', 'contact_phone', 'evidence_name', 'review_note', 'reviewed_by']));

        return ['status' => $status, 'events' => $events, 'counts' => MotoringEvent::selectRaw('status, COUNT(*) AS count')->groupBy('status')->pluck('count', 'status')];
    }

    public function store(EventSubmissionRequest $r)
    {
        return $this->save($r, new MotoringEvent);
    }

    public function update(EventSubmissionRequest $r, MotoringEvent $event)
    {
        return $this->save($r, $event);
    }

    public function adminUpdate(AdminEventUpdateRequest $r, MotoringEvent $event)
    {
        $d = $r->validated();
        $newPath = null;

        try {
            DB::transaction(function () use ($r, $event, $d, &$newPath) {
                $record = MotoringEvent::lockForUpdate()->findOrFail($event->id);
                $previous = $record->getAttributes();
                $newVersion = $record->version + 1;
                $attributes = collect($d)->except('starts_at_local', 'ends_at_local', 'poster')->all();
                $attributes['starts_at'] = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $d['starts_at_local'], 'Africa/Nairobi')->setTimezone('UTC');
                $attributes['ends_at'] = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $d['ends_at_local'], 'Africa/Nairobi')->setTimezone('UTC');
                if ($r->hasFile('poster')) {
                    $newPath = $r->file('poster')->store('events', 'local');
                    $attributes['poster_path'] = $newPath;
                }
                $attributes['version'] = $newVersion;
                $attributes['approved_version'] = $record->status === 'approved' ? $newVersion : null;
                $record->fill($attributes)->save();
                Workflow::audit('event.admin_updated', $record, ['previous' => $previous, 'poster_changed' => (bool) $newPath]);
            });
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $e;
        }

        return back()->with('message', 'Event details and poster updated.');
    }

    private function save(EventSubmissionRequest $r, MotoringEvent $event)
    {
        $d = $r->validated();
        $paths = [];
        try {
            DB::transaction(function () use ($r, $event, $d, &$paths) {
                $record = $event->exists ? MotoringEvent::lockForUpdate()->findOrFail($event->id) : $event;
                if ($record->exists) {
                    abort_unless($this->policy->update($r->user(), $record), 403);
                }$previous = $record->exists ? $record->getAttributes() : null;
                $attributes = collect($d)->except('starts_at_local', 'ends_at_local', 'poster', 'evidence')->all();
                $attributes['starts_at'] = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $d['starts_at_local'], 'Africa/Nairobi')->setTimezone('UTC');
                $attributes['ends_at'] = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $d['ends_at_local'], 'Africa/Nairobi')->setTimezone('UTC');
                foreach (['poster' => 'poster_path', 'evidence' => 'evidence_path'] as $field => $column) {
                    if ($r->hasFile($field)) {
                        $paths[] = $path = $r->file($field)->store('events', 'local');
                        $attributes[$column] = $path;
                        if ($field === 'evidence') {
                            $attributes['evidence_name'] = basename($r->file($field)->getClientOriginalName());
                        }
                    }
                }
                $record->fill($attributes + ['user_id' => $r->user()->id, 'status' => 'pending', 'version' => $record->exists ? $record->version + 1 : 1, 'approved_version' => null, 'review_note' => null, 'approval_scope' => null, 'reviewed_by' => null, 'reviewed_at' => null])->save();
                Workflow::audit($previous ? 'event.resubmitted' : 'event.submitted', $record, ['previous' => $previous]);
            });
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return redirect('/events/mine')->with('message', 'Event submitted for review. It becomes visible to others after approval.');
    }

    public function review(Request $r, MotoringEvent $event)
    {
        $this->admin($r);
        $d = $r->validate(['status' => 'required|in:pending,approved,rejected', 'version' => 'required|integer|min:1', 'review_note' => 'required|string|max:3000', 'approval_scope' => 'nullable|required_if:status,approved|string|max:2000', 'evidence_checked' => 'accepted_if:status,approved']);
        DB::transaction(function () use ($r, $event, $d) {
            $record = MotoringEvent::lockForUpdate()->findOrFail($event->id);
            if ($record->version !== (int) $d['version']) {
                throw ValidationException::withMessages(['version' => 'This event changed since you opened it. Refresh and review the latest submission.']);
            }if ($record->status === 'withdrawn') {
                throw ValidationException::withMessages(['status' => 'The organizer withdrew this event. It must be resubmitted before approval.']);
            }if ($d['status'] === 'approved') {
                if ($record->starts_at->isPast()) {
                    throw ValidationException::withMessages(['status' => 'This event has already started. The organizer must update its schedule.']);
                }if (! Storage::disk('local')->exists($record->evidence_path)) {
                    throw ValidationException::withMessages(['evidence_checked' => 'The supporting evidence must be available before approval.']);
                }
            }
            Workflow::audit('event.reviewed', $record, ['previous' => $record->only('status', 'version', 'approved_version', 'review_note', 'approval_scope', 'reviewed_by', 'reviewed_at'), 'decision' => $d]);
            $record->update(collect($d)->only('status', 'review_note', 'approval_scope')->all() + ['reviewed_by' => $r->user()->id, 'reviewed_at' => now(), 'approved_version' => $d['status'] === 'approved' ? $record->version : null]);
        });

        return back()->with('message', 'Event decision saved. Only approved submissions are listed.');
    }

    public function withdraw(Request $r, MotoringEvent $event)
    {
        abort_unless($this->policy->update($r->user(), $event), 403);
        DB::transaction(function () use ($event) {
            $record = MotoringEvent::lockForUpdate()->findOrFail($event->id);
            Workflow::audit('event.withdrawn', $record, ['previous' => $record->only('status', 'version', 'approved_version')]);
            $record->update(['status' => 'withdrawn', 'approved_version' => null]);
        });

        return back()->with('message', 'Event withdrawn from the listings.');
    }

    public function evidence(Request $r, MotoringEvent $event)
    {
        abort_unless($this->policy->evidence($r->user(), $event), 403);

        return Storage::disk('local')->download($event->evidence_path, $event->evidence_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function poster(Request $r, MotoringEvent $event)
    {
        $published = $event->status === 'approved' && $event->approved_version === $event->version;
        abort_unless($event->poster_path && ($published || ($r->user() && $this->policy->evidence($r->user(), $event))), 404);

        return response()->file(Storage::disk('local')->path($event->poster_path), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => $published ? 'public, max-age=3600' : 'private, no-store']);
    }
}
