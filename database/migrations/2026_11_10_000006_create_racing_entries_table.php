<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('racing_entries', function (Blueprint $t) {
            $t->id();
            $t->string('driver');
            $t->string('vehicle');
            $t->string('event')->index();
            $t->string('venue');
            $t->date('race_date');
            $t->unsignedInteger('distance_metres');
            $t->unsignedInteger('elapsed_ms');
            $t->string('gps_path');
            $t->string('gps_name');
            $t->string('slip_path');
            $t->string('slip_name');
            $t->string('status')->default('pending')->index();
            $t->text('review_note')->nullable();
            $t->foreignId('created_by')->index()->constrained('users');
            $t->foreignId('reviewed_by')->nullable()->index()->constrained('users');
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->index(['event', 'distance_metres', 'status', 'elapsed_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('racing_entries');
    }
};
