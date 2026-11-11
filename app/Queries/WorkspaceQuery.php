<?php

namespace App\Queries;

use App\Models\Article;
use App\Models\Attachment;
use App\Models\AuditEvent;
use App\Models\Community;
use App\Models\Complaint;
use App\Models\DeletionRequest;
use App\Models\Fitment;
use App\Models\HistoryShare;
use App\Models\Organization;
use App\Models\Part;
use App\Models\Reminder;
use App\Models\Review;
use App\Models\ServiceRecord;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WorkspaceQuery
{
    public function props(Request $r, string $section = 'overview'): array
    {
        $u = $r->user();
        $ids = $u->organizations()->where('status', 'approved')->pluck('organizations.id');
        $vehicles = $u->vehicles()->latest()->get();
        $active = $vehicles->firstWhere('id', (int) $r->session()->get('active_vehicle')) ?? $vehicles->first();
        $requests = ServiceRequest::with(['vehicle' => fn ($q) => $q->select('id', 'make', 'model', 'year', 'variant', 'engine_code', 'fuel_type', 'transmission'), 'quotes.organization', 'transaction', 'attachments'])->where(fn ($q) => $q->where('user_id', $u->id)->orWhereIn('organization_id', $ids)->when($ids->isNotEmpty(), fn ($q) => $q->orWhereNull('organization_id')))->latest()->paginate(12, ['*'], 'requests_page')->withQueryString();
        $requests->getCollection()->each(function ($req) use ($u, $ids) {
            if ($req->user_id !== $u->id) {
                $req->setRelation('quotes', $req->quotes->whereIn('organization_id', $ids)->values());
            }
        });
        $transactions = Transaction::with(['request.vehicle' => fn ($q) => $q->select('id', 'make', 'model', 'year', 'variant', 'engine_code', 'fuel_type', 'transmission'), 'organization', 'review', 'complaints.attachments'])->where(fn ($q) => $q->where('user_id', $u->id)->orWhereIn('organization_id', $ids))->latest()->paginate(12, ['*'], 'orders_page')->withQueryString();
        $parts = Part::with('organization')->whereHas('organization', fn ($q) => $q->where('status', 'approved'))->when($r->q, fn ($q) => $q->where(fn ($q) => $q->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($r->q).'%'])->orWhereRaw('LOWER(oem_reference) LIKE ?', ['%'.mb_strtolower($r->q).'%'])))->when($r->category, fn ($q) => $q->where('category', $r->category))->latest()->paginate(12, ['*'], 'parts_page')->withQueryString();
        $fitments = $active ? Fitment::with('assessor:id,name')->where('vehicle_id', $active->id)->whereIn('part_id', $parts->pluck('id'))->get()->keyBy('part_id') : collect();
        $parts->getCollection()->transform(function ($p) use ($active, $fitments) {
            $f = $fitments->get($p->id);
            $p->fitment_status = $f && $active && $f->vehicle_fingerprint === $active->fingerprint() && $f->part_fingerprint === $p->fingerprint() ? $f->status : 'unknown';
            $p->fitment_evidence = $f?->evidence;
            $p->fitment_assessor = $f?->assessor?->name;
            $p->fitment_assessed_at = $f?->assessed_at?->toIso8601String();

            return $p;
        });
        $providers = $this->providers();

        $data = [
            'demoData' => str_ends_with($u->email, '@carverse.test') && ! app()->environment('production'), 'section' => $section, 'vehicles' => $vehicles, 'activeVehicle' => $active, 'requests' => $requests, 'transactions' => $transactions, 'parts' => $parts, 'providers' => $providers, 'organizations' => $u->organizations()->get()->makeVisible(['review_note', 'qualifications']), 'providerParts' => Part::with('organization')->whereIn('organization_id', $ids)->latest()->paginate(12, ['*'], 'provider_parts_page'),
            'records' => ServiceRecord::with(['attachments', 'revisions'])->whereIn('vehicle_id', $vehicles->pluck('id'))->latest('service_date')->get(),
            'reminders' => Reminder::with('vehicle')->whereIn('vehicle_id', $vehicles->pluck('id'))->get(),
            'shares' => HistoryShare::whereIn('vehicle_id', $vehicles->pluck('id'))->get(['id', 'vehicle_id', 'record_ids', 'expires_at', 'revoked_at']),
            'articles' => Article::where('status', 'published')->latest()->get(), 'communities' => Community::all(),
            'notifications' => $u->notifications()->latest()->limit(10)->get(),
            'admin' => $u->role === 'admin' ? ['organizations' => Organization::latest()->get(), 'users' => User::paginate(20, ['*'], 'users_page'), 'attachments' => Attachment::whereNotNull('organization_id')->get(), 'parts' => Part::with('organization', 'fitments.assessor:id,name')->latest()->limit(100)->get(), 'pilot_requests' => ServiceRequest::with('vehicle:id,make,model,year', 'quotes.organization', 'attachments')->latest()->paginate(20, ['*'], 'pilot_requests_page'), 'records' => ServiceRecord::with('attachments')->latest()->limit(30)->get(), 'transactions' => Transaction::with('organization', 'request.vehicle:id,make,model,year')->latest()->paginate(20, ['*'], 'admin_orders_page')->through(fn ($t) => $t->makeVisible(['commission_minor', 'variable_cost_minor'])), 'deletions' => DeletionRequest::with('user')->latest()->get(), 'audits' => AuditEvent::latest()->limit(60)->get(), 'articles' => Article::all(), 'metrics' => $this->metrics()] : null,
            'disputes' => in_array($u->role, ['admin', 'moderator']) ? Transaction::with('organization')->where('status', 'disputed')->latest()->limit(30)->get() : [],
            'moderation' => in_array($u->role, ['moderator', 'admin']) ? Complaint::with(['transaction.organization', 'attachments'])->latest()->paginate(20) : null,
            'paymentMode' => config('payments.driver'), 'filters' => $r->only('q', 'category'), 'flash' => ['message' => session('message'), 'share_url' => session('share_url')],
        ];
        if (! config('carverse.legacy_workflows')) {
            foreach (['requests', 'transactions', 'records', 'reminders', 'shares', 'notifications', 'disputes'] as $key) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    public function providers(): Collection
    {
        $ratings = Review::query()->join('transactions', 'transactions.id', '=', 'reviews.transaction_id')->selectRaw('transactions.organization_id, COUNT(*) as review_count, AVG(quality) as quality, AVG(communication) as communication, AVG(timeliness) as timeliness, AVG(value) as value')->groupBy('transactions.organization_id')->get()->keyBy('organization_id');
        $reviews = Review::with('transaction:id,organization_id')->latest()->limit(100)->get()->groupBy(fn ($review) => $review->transaction->organization_id);

        return Organization::where('status', 'approved')->get()->map(function ($o) use ($ratings, $reviews) {
            $rating = $ratings->get($o->id);
            $o->review_count = (int) ($rating?->review_count ?? 0);
            $o->ratings = collect(['quality', 'communication', 'timeliness', 'value'])->mapWithKeys(fn ($k) => [$k => $rating?->{$k} === null ? null : (float) $rating->{$k}]);
            $o->public_reviews = $reviews->get($o->id, collect())->take(5)->map(fn ($review) => $review->only('quality', 'communication', 'timeliness', 'value', 'body', 'response'))->values();

            return $o;
        });
    }

    private function metrics(): array
    {
        $done = Transaction::where('status', 'completed');
        $complete = (clone $done)->count();
        $costsMissing = (clone $done)->whereNull('variable_cost_minor')->exists();

        return ['requests' => ServiceRequest::count(), 'quotes_accepted' => Transaction::count(), 'completed' => $complete, 'mean_fulfilment_hours' => $complete ? round((clone $done)->get()->avg(fn ($t) => $t->created_at->diffInHours($t->completed_at)), 1) : null, 'fitment_returns' => Transaction::where('fitment_return', true)->count(), 'repeat_customers' => Transaction::where('status', 'completed')->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->get()->count(), 'commission_minor' => (clone $done)->sum('commission_minor'), 'variable_cost_minor' => (clone $done)->sum('variable_cost_minor'), 'cost_data_incomplete' => $costsMissing, 'contribution_minor' => $costsMissing ? null : (clone $done)->sum('commission_minor') - (clone $done)->sum('variable_cost_minor')];
    }
}
