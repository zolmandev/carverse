<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['vehicles', 'parts', 'organizations'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->string('photo_path')->nullable());
        }
        Schema::table('service_records', function (Blueprint $t) {
            $t->string('provider_name')->nullable();
            $t->timestamp('evidence_reviewed_at')->nullable();
        });
        Schema::table('payment_attempts', function (Blueprint $t) {
            $t->string('merchant_reference')->nullable();
            $t->unsignedBigInteger('callback_amount_minor')->nullable();
        });
        Schema::create('deletion_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('status')->default('requested');
            $t->text('retention_note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');
        foreach (['vehicles', 'parts', 'organizations'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('photo_path'));
        }Schema::table('service_records', fn (Blueprint $t) => $t->dropColumn(['provider_name', 'evidence_reviewed_at']));
        Schema::table('payment_attempts', fn (Blueprint $t) => $t->dropColumn(['merchant_reference', 'callback_amount_minor']));
    }
};
