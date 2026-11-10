<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = ['vehicles' => ['user_id'], 'parts' => ['organization_id'], 'fitments' => ['vehicle_id', 'assessor_id'], 'service_requests' => ['user_id', 'vehicle_id', 'organization_id', 'part_id'], 'quotes' => ['service_request_id', 'organization_id'], 'transactions' => ['user_id', 'organization_id'], 'payment_attempts' => ['transaction_id'], 'service_records' => ['vehicle_id', 'author_id'], 'record_revisions' => ['service_record_id', 'actor_id'], 'reminders' => ['vehicle_id'], 'reviews' => ['user_id'], 'complaints' => ['transaction_id', 'user_id'], 'attachments' => ['user_id', 'service_record_id', 'complaint_id', 'service_request_id', 'organization_id'], 'history_shares' => ['vehicle_id'], 'audit_events' => ['actor_id'], 'deletion_requests' => ['user_id'], 'organization_user' => ['user_id']];

    public function up(): void
    {
        foreach ($this->indexes as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach ($columns as $column) {
                    $t->index($column);
                }
            });
        }Schema::table('reminders', fn (Blueprint $t) => $t->index(['notified_at', 'due_date']));
        Schema::table('transactions', fn (Blueprint $t) => $t->index(['status', 'completed_at']));
    }

    public function down(): void
    {
        Schema::table('transactions', fn (Blueprint $t) => $t->dropIndex(['status', 'completed_at']));
        Schema::table('reminders', fn (Blueprint $t) => $t->dropIndex(['notified_at', 'due_date']));
        foreach ($this->indexes as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach ($columns as $column) {
                    $t->dropIndex([$column]);
                }
            });
        }
    }
};
