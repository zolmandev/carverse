<?php

namespace App\Jobs;

use App\Models\Reminder;
use App\Notifications\MaintenanceDue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SendMaintenanceReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reminderId) {}

    public function handle(): void
    {
        if (! config('carverse.legacy_workflows')) {
            return;
        }
        DB::transaction(function () {
            $r = Reminder::with('vehicle.owner')->lockForUpdate()->find($this->reminderId);
            if (! $r || $r->notified_at || ! $r->vehicle->owner->reminder_notifications) {
                return;
            }
            $due = ($r->due_date && $r->due_date->lte(today())) || ($r->due_mileage !== null && $r->vehicle->mileage >= $r->due_mileage);
            if (! $due) {
                return;
            }
            $r->vehicle->owner->notify(new MaintenanceDue(['title' => $r->title, 'vehicle' => $r->vehicle->make.' '.$r->vehicle->model, 'source' => $r->source, 'mileage_stale' => $r->vehicle->mileage_date->lt(today()->subDays(30))]));
            $r->update(['notified_at' => now()]);
        });
    }
}
