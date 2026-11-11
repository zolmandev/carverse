<?php

namespace App\Http\Controllers;

use App\Actions\Workflow;
use App\Models\CarBrand;
use App\Models\Community;
use App\Models\Organization;
use App\Models\RacingEntry;
use App\Policies\WorkspacePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SuperUserController extends Controller
{
    private function authorizeAdmin(Request $r): void
    {
        abort_unless((new WorkspacePolicy)->admin($r->user()), 403);
    }

    public function index(Request $r)
    {
        $this->authorizeAdmin($r);
        $d = $r->validate(['q' => 'nullable|string|max:160', 'tab' => 'nullable|in:groups,racing,cars,approvals', 'provider_status' => 'nullable|in:pending,approved,rejected', 'provider_kind' => 'nullable|in:garage,mechanic,supplier', 'event' => 'nullable|string|max:160', 'distance' => 'nullable|in:201,402']);

        $applications = null;
        if (($d['tab'] ?? 'groups') === 'approvals') {
            $query = Organization::with(['members:id,name,email', 'attachments', 'reviewer:id,name'])->where('status', $d['provider_status'] ?? 'pending')->when($d['provider_kind'] ?? null, fn ($q, $kind) => $q->where('kind', $kind))->when($d['q'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])->orWhereRaw('LOWER(location) LIKE ?', ['%'.mb_strtolower($search).'%'])));
            $applications = ['status' => $d['provider_status'] ?? 'pending', 'counts' => Organization::selectRaw('status, COUNT(*) AS count')->groupBy('status')->pluck('count', 'status'), 'providers' => $query->latest()->paginate(15, ['*'], 'applications_page')->withQueryString()->through(fn ($o) => $o->makeVisible(['qualifications', 'review_note', 'reviewed_by']))];
        }

        return Inertia::render('carverse/super-user', ['applications' => $applications, 'carBrands' => CarBrand::when(($d['tab'] ?? 'groups') !== 'cars', fn ($q) => $q->where('is_active', true))->orderBy('name')->get(), 'catalog' => ($d['tab'] ?? 'groups') === 'cars' ? app(CarCatalogController::class)->page($r) : null, 'tab' => $d['tab'] ?? 'groups', 'groups' => Community::with('brand', 'carModel', 'trim')->when($d['q'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($value).'%'])->orWhereRaw('LOWER(model) LIKE ?', ['%'.mb_strtolower($value).'%'])->orWhereHas('brand', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($value).'%']))->orWhereHas('carModel', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($value).'%']))))->latest()->paginate(12, ['*'], 'groups_page')->withQueryString(), 'entries' => RacingEntry::with('reviewer:id,name')->when($d['event'] ?? null, fn ($q, $value) => $q->where('event', $value))->when($d['distance'] ?? null, fn ($q, $value) => $q->where('distance_metres', $value))->orderBy('elapsed_ms')->paginate(20, ['*'], 'entries_page')->withQueryString(), 'leaderboard' => ! empty($d['event']) && ! empty($d['distance']) ? RacingEntry::where('status', 'verified')->where('event', $d['event'])->where('distance_metres', $d['distance'])->orderBy('elapsed_ms')->orderBy('id')->limit(100)->get() : [], 'events' => RacingEntry::select('event')->distinct()->orderBy('event')->pluck('event'), 'filters' => $d, 'flash' => ['message' => session('message')]]);
    }

    public function storeRace(Request $r)
    {
        $this->authorizeAdmin($r);
        $d = $r->validate(['driver' => 'required|string|max:120', 'vehicle' => 'required|string|max:160', 'event' => 'required|string|max:160', 'venue' => 'required|string|max:160', 'race_date' => 'required|date|before_or_equal:today', 'distance_metres' => 'required|in:201,402', 'elapsed_seconds' => ['required', 'numeric', 'min:1', 'max:120', 'regex:/^\d+(\.\d{1,3})?$/'], 'gps' => 'required|file|extensions:gpx,json,csv,pdf,jpg,jpeg,png|max:5120', 'slip' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $paths = [];
        try {
            DB::transaction(function () use ($r, $d, &$paths) {
                $paths[] = $gps = $r->file('gps')->store('racing', 'local');
                $paths[] = $slip = $r->file('slip')->store('racing', 'local');
                $entry = RacingEntry::create(collect($d)->except('elapsed_seconds', 'gps', 'slip')->all() + ['elapsed_ms' => (int) round((float) $d['elapsed_seconds'] * 1000), 'gps_path' => $gps, 'gps_name' => basename($r->file('gps')->getClientOriginalName()), 'slip_path' => $slip, 'slip_name' => basename($r->file('slip')->getClientOriginalName()), 'created_by' => $r->user()->id]);
                Workflow::audit('racing.submitted', $entry);
            });
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return redirect('/app/admin?tab=racing')->with('message', 'Run saved as pending. Review both evidence files before verification.');
    }

    public function reviewRace(Request $r, RacingEntry $entry)
    {
        $this->authorizeAdmin($r);
        $d = $r->validate(['status' => 'required|in:verified,rejected,pending', 'review_note' => 'required|string|max:3000', 'gps_checked' => 'accepted_if:status,verified', 'slip_checked' => 'accepted_if:status,verified', 'matching_run' => 'accepted_if:status,verified']);
        DB::transaction(function () use ($entry, $r, $d) {
            $locked = RacingEntry::lockForUpdate()->findOrFail($entry->id);
            if ($d['status'] === 'verified') {
                abort_unless(Storage::disk('local')->exists($locked->gps_path) && Storage::disk('local')->exists($locked->slip_path), 422, 'Both evidence files must be available.');
            }Workflow::audit('racing.reviewed', $locked, ['previous' => $locked->only('status', 'review_note', 'reviewed_by', 'reviewed_at'), 'status' => $d['status'], 'review_note' => $d['review_note'], 'gps_checked' => $r->boolean('gps_checked'), 'slip_checked' => $r->boolean('slip_checked'), 'matching_run' => $r->boolean('matching_run')]);
            $locked->update(['status' => $d['status'], 'review_note' => $d['review_note'], 'reviewed_by' => $r->user()->id, 'reviewed_at' => now()]);
        });

        return redirect('/app/admin?tab=racing')->with('message', 'Evidence review saved.');
    }

    public function evidence(Request $r, RacingEntry $entry, string $kind)
    {
        $this->authorizeAdmin($r);
        abort_unless(in_array($kind, ['gps', 'slip']), 404);

        return Storage::disk('local')->download($entry->{$kind.'_path'}, $entry->{$kind.'_name'}, ['X-Content-Type-Options' => 'nosniff']);
    }
}
