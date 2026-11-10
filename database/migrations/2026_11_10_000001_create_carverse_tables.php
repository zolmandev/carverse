<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('owner');
            $t->string('phone')->nullable();
            $t->string('location')->default('Nairobi');
            $t->boolean('reminder_notifications')->default(true);
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('kind');
            $t->string('location');
            $t->string('phone')->nullable();
            $t->text('description')->nullable();
            $t->string('status')->default('pending');
            $t->text('verification_scope')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->json('specialties')->nullable();
            $t->string('opening_hours')->nullable();
            $t->timestamps();
        });
        Schema::create('organization_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unique(['organization_id', 'user_id']);
        });
        Schema::create('vehicles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('make')->index();
            $t->string('model')->index();
            $t->unsignedSmallInteger('year');
            $t->string('variant')->nullable();
            $t->string('engine_code')->nullable();
            $t->string('fuel_type');
            $t->string('transmission');
            $t->text('identifier')->nullable();
            $t->string('registration')->nullable();
            $t->unsignedInteger('mileage');
            $t->date('mileage_date');
            $t->timestamps();
        });
        Schema::create('parts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained();
            $t->string('title')->index();
            $t->string('brand');
            $t->string('category')->index();
            $t->text('description');
            $t->string('oem_reference')->nullable();
            $t->string('condition');
            $t->unsignedBigInteger('price_minor')->nullable();
            $t->string('stock');
            $t->timestamps();
        });
        Schema::create('fitments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('part_id')->constrained();
            $t->foreignId('vehicle_id')->constrained();
            $t->foreignId('assessor_id')->constrained('users');
            $t->string('status');
            $t->text('evidence');
            $t->string('vehicle_fingerprint');
            $t->string('part_fingerprint');
            $t->timestamp('assessed_at');
            $t->timestamps();
            $t->unique(['part_id', 'vehicle_id']);
        });
        Schema::create('service_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('vehicle_id')->constrained();
            $t->foreignId('organization_id')->nullable()->constrained();
            $t->foreignId('part_id')->nullable()->constrained();
            $t->string('kind');
            $t->string('title');
            $t->text('notes');
            $t->string('status')->default('requested')->index();
            $t->timestamps();
        });
        Schema::create('quotes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_request_id')->constrained();
            $t->foreignId('organization_id')->constrained();
            $t->json('items');
            $t->unsignedBigInteger('total_minor');
            $t->date('valid_until');
            $t->string('duration');
            $t->timestamps();
        });
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_request_id')->unique()->constrained();
            $t->foreignId('quote_id')->unique()->constrained();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('organization_id')->constrained();
            $t->json('quote_snapshot');
            $t->unsignedBigInteger('total_minor');
            $t->string('status')->default('accepted');
            $t->string('payment_status')->default('unpaid');
            $t->unsignedBigInteger('commission_minor')->default(0);
            $t->unsignedBigInteger('variable_cost_minor')->nullable();
            $t->boolean('fitment_return')->default(false);
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('payment_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transaction_id')->constrained();
            $t->string('reference')->unique();
            $t->string('provider');
            $t->string('provider_reference')->nullable()->unique();
            $t->unsignedBigInteger('amount_minor');
            $t->string('currency')->default('KES');
            $t->string('status')->default('pending');
            $t->timestamps();
        });
        Schema::create('service_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vehicle_id')->constrained();
            $t->foreignId('author_id')->constrained('users');
            $t->foreignId('transaction_id')->nullable()->unique()->constrained();
            $t->date('service_date');
            $t->unsignedInteger('mileage');
            $t->text('work');
            $t->text('parts_used')->nullable();
            $t->string('provenance')->default('owner-submitted');
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
        });
        Schema::create('record_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_record_id')->constrained();
            $t->foreignId('actor_id')->constrained('users');
            $t->json('previous');
            $t->text('reason');
            $t->timestamps();
        });
        Schema::create('reminders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vehicle_id')->constrained();
            $t->string('title');
            $t->date('due_date')->nullable();
            $t->unsignedInteger('due_mileage')->nullable();
            $t->text('source')->default('Owner-defined interval');
            $t->timestamp('notified_at')->nullable();
            $t->timestamps();
        });
        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transaction_id')->unique()->constrained();
            $t->foreignId('user_id')->constrained();
            foreach (['quality', 'communication', 'timeliness', 'value'] as $c) {
                $t->unsignedTinyInteger($c);
            }$t->text('body');
            $t->text('response')->nullable();
            $t->timestamps();
        });
        Schema::create('complaints', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transaction_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->text('body');
            $t->text('provider_response')->nullable();
            $t->string('status')->default('open');
            $t->text('decision')->nullable();
            $t->text('appeal')->nullable();
            $t->text('public_summary')->nullable();
            $t->timestamps();
        });
        Schema::create('attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('service_record_id')->nullable()->constrained();
            $t->foreignId('complaint_id')->nullable()->constrained();
            $t->foreignId('service_request_id')->nullable()->constrained();
            $t->foreignId('organization_id')->nullable()->constrained();
            $t->string('path');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('history_shares', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vehicle_id')->constrained();
            $t->string('token_hash')->unique();
            $t->json('record_ids');
            $t->timestamp('expires_at');
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
        });
        Schema::create('articles', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('author');
            $t->text('body');
            $t->text('sources');
            $t->string('status')->default('draft');
            $t->date('published_at')->nullable();
            $t->date('reviewed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('communities', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('model');
            $t->string('url');
            $t->timestamps();
        });
        Schema::create('audit_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users');
            $t->string('action')->index();
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->json('details')->nullable();
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'audit_events', 'communities', 'articles', 'history_shares', 'attachments', 'complaints', 'reviews', 'reminders', 'record_revisions', 'service_records', 'payment_attempts', 'transactions', 'quotes', 'service_requests', 'fitments', 'parts', 'vehicles', 'organization_user', 'organizations'] as $table) {
            Schema::dropIfExists($table);
        }Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'phone', 'location', 'reminder_notifications']));
    }
};
