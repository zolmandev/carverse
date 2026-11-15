<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motoring_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete()->index();
            $t->string('title', 180);
            $t->string('category');
            $t->text('description');
            $t->string('organizer_name', 160);
            $t->string('contact_email');
            $t->string('contact_phone', 30);
            $t->string('venue', 180);
            $t->string('location', 180);
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');
            $t->text('website_url')->nullable();
            $t->string('poster_path')->nullable();
            $t->string('evidence_path');
            $t->string('evidence_name');
            $t->string('status')->default('pending');
            $t->unsignedInteger('version')->default(1);
            $t->unsignedInteger('approved_version')->nullable();
            $t->text('review_note')->nullable();
            $t->text('approval_scope')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete()->index();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'starts_at']);
            $t->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motoring_events');
    }
};
