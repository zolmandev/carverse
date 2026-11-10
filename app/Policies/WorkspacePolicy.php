<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\ServiceRecord;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vehicle;

class WorkspacePolicy
{
    public function vehicle(User $u, Vehicle $v): bool
    {
        return $v->user_id === $u->id;
    }

    public function request(User $u, ServiceRequest $r): bool
    {
        return $r->user_id === $u->id || ($r->organization_id && $u->inOrganization($r->organization_id)) || (! $r->organization_id && $u->organizations()->where('status', 'approved')->exists());
    }

    public function transaction(User $u, Transaction $t): bool
    {
        return $u->id === $t->user_id || $u->inOrganization($t->organization_id);
    }

    public function record(User $u, ServiceRecord $r): bool
    {
        return $r->vehicle->user_id === $u->id;
    }

    public function moderate(User $u): bool
    {
        return in_array($u->role, ['moderator', 'admin']);
    }

    public function admin(User $u): bool
    {
        return $u->role === 'admin';
    }

    public function attachment(User $u, Attachment $a): bool
    {
        if ($a->user_id === $u->id) {
            return true;
        }
        if ($a->complaint_id) {
            return $this->moderate($u) || $u->inOrganization($a->complaint->transaction->organization_id);
        }
        if ($a->service_record_id) {
            return $this->record($u, $a->record) || $this->moderate($u);
        }
        if ($a->service_request_id) {
            return $this->request($u, $a->request);
        }

        return $a->organization_id && $this->admin($u);
    }
}
