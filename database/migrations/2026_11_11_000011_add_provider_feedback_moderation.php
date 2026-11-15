<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['reviews', 'complaints'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('moderation_status')->default('pending');
                $t->text('moderation_note')->nullable();
                $t->foreignId('reviewed_by')->nullable()->index()->constrained('users')->restrictOnDelete();
                $t->timestamp('reviewed_at')->nullable();
                $t->index(['moderation_status', 'created_at']);
            });
        }
        Schema::table('reviews', fn (Blueprint $t) => $t->string('response_status')->default('pending'));
        Schema::table('complaints', function (Blueprint $t) {
            $t->foreignId('public_approved_by')->nullable()->index()->constrained('users')->restrictOnDelete();
            $t->timestamp('public_approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $t) {
            $t->dropConstrainedForeignId('public_approved_by');
            $t->dropColumn('public_approved_at');
        });
        Schema::table('reviews', fn (Blueprint $t) => $t->dropColumn('response_status'));
        foreach (['reviews', 'complaints'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['moderation_status', 'created_at']);
                $t->dropConstrainedForeignId('reviewed_by');
                $t->dropColumn(['moderation_status', 'moderation_note', 'reviewed_at']);
            });
        }
    }
};
