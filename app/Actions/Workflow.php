<?php

namespace App\Actions;

use App\Models\AuditEvent;
use App\Models\PaymentAttempt;
use App\Models\Quote;
use App\Models\RecordRevision;
use App\Models\ServiceRecord;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Workflow
{
    public static function audit(string $action, object $subject, array $details = [], ?int $actor = null): void
    {
        AuditEvent::create(['actor_id' => $actor ?? auth()->id(), 'action' => $action, 'subject_type' => class_basename($subject), 'subject_id' => $subject->id, 'details' => $details]);
    }

    public function accept(Quote $quote, User $owner): Transaction
    {
        return DB::transaction(function () use ($quote, $owner) {
            $r = ServiceRequest::lockForUpdate()->findOrFail($quote->service_request_id);
            abort_unless($r->user_id === $owner->id, 403);
            $q = Quote::lockForUpdate()->findOrFail($quote->id);
            if (! in_array($r->status, ['requested', 'quoted']) || $r->transaction()->exists() || $q->valid_until->copy()->setTimezone('Africa/Nairobi')->endOfDay()->isPast()) {
                throw ValidationException::withMessages(['quote' => 'This request already has an accepted quote, or the quote has expired.']);
            }
            abort_unless($q->organization->status === 'approved', 403);
            $t = Transaction::create(['service_request_id' => $r->id, 'quote_id' => $q->id, 'user_id' => $owner->id, 'organization_id' => $q->organization_id, 'quote_snapshot' => ['items' => $q->items, 'duration' => $q->duration, 'valid_until' => $q->valid_until->toDateString(), 'provider' => $q->organization->name], 'total_minor' => $q->total_minor]);
            $r->update(['status' => 'accepted', 'organization_id' => $q->organization_id]);
            self::audit('quote.accepted', $t, ['quote_id' => $q->id], $owner->id);

            return $t;
        });
    }

    public function transition(Transaction $transaction, string $next, User $actor): void
    {
        DB::transaction(function () use ($transaction, $next, $actor) {
            $t = Transaction::lockForUpdate()->findOrFail($transaction->id);
            $owner = $t->user_id === $actor->id;
            $provider = $actor->inOrganization($t->organization_id);
            $allowed = match ($t->status) {
                'accepted' => ['in_progress', 'cancelled', 'disputed'],'in_progress' => ['awaiting_confirmation', 'disputed'],'awaiting_confirmation' => ['completed', 'disputed'],'disputed' => ['cancelled', 'in_progress'],default => []
            };
            abort_unless(in_array($next, $allowed), 422, 'This status transition is not permitted.');
            if (in_array($next, ['in_progress', 'awaiting_confirmation'])) {
                abort_unless($provider || ($t->status === 'disputed' && in_array($actor->role, ['admin', 'moderator'])), 403);
            } elseif ($next === 'completed') {
                abort_unless($owner, 403);
            } elseif ($t->status === 'disputed') {
                abort_unless(in_array($actor->role, ['admin', 'moderator']), 403);
            } else {
                abort_unless($owner || $provider, 403);
            }
            $before = $t->status;
            $t->update(['status' => $next, 'completed_at' => $next === 'completed' ? now() : null]);
            $t->request->update(['status' => $next]);
            if ($next === 'completed') {
                $v = $t->request->vehicle;
                ServiceRecord::firstOrCreate(['transaction_id' => $t->id], ['vehicle_id' => $v->id, 'author_id' => $actor->id, 'service_date' => today(), 'mileage' => $v->mileage, 'work' => $t->request->title, 'parts_used' => collect($t->quote_snapshot['items'])->pluck('description')->implode(', '), 'provenance' => 'provider-submitted', 'provider_name' => $t->organization->name]);
            }
            self::audit('transaction.status', $t, ['from' => $before, 'to' => $next], $actor->id);
        });
    }

    public function correct(ServiceRecord $record, array $data, string $reason, User $actor): void
    {
        DB::transaction(function () use ($record, $data, $reason, $actor) {
            $r = ServiceRecord::lockForUpdate()->findOrFail($record->id);
            abort_unless($r->vehicle->user_id === $actor->id, 403);
            RecordRevision::create(['service_record_id' => $r->id, 'actor_id' => $actor->id, 'previous' => $r->getAttributes(), 'reason' => $reason]);
            $r->update([...$data, 'version' => $r->version + 1, 'evidence_reviewed_at' => null, 'provenance' => $r->transaction_id ? 'provider-submitted' : 'owner-submitted']);
            self::audit('record.corrected', $r, ['reason' => $reason], $actor->id);
        });
    }

    public function reconcile(string $reference, string $providerReference, int $amount, string $currency, bool $success): void
    {
        DB::transaction(function () use ($reference, $providerReference, $amount, $currency, $success) {
            $p = PaymentAttempt::where('reference', $reference)->lockForUpdate()->firstOrFail();
            abort_unless($p->amount_minor === $amount && $currency === 'KES' && $p->transaction->total_minor === $amount, 422, 'Payment amount or currency mismatch.');
            abort_unless(! $p->provider_reference || $p->provider_reference === $providerReference, 422, 'Payment reference mismatch.');
            if ($p->status === 'paid') {
                return;
            }$p->update(['provider_reference' => $providerReference, 'status' => $success ? 'paid' : 'failed']);
            if ($success) {
                $p->transaction->update(['payment_status' => 'paid']);
            }self::audit('payment.reconciled', $p, ['status' => $p->status]);
        });
    }
}
