<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $t) {
            $t->unsignedTinyInteger('years_experience')->nullable();
            $t->text('qualifications')->nullable();
            $t->text('review_note')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete()->index();
            $t->timestamp('reviewed_at')->nullable();
            $t->index(['status', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $t) {
            $t->dropIndex(['status', 'kind']);
            $t->dropConstrainedForeignId('reviewed_by');
            $t->dropColumn(['years_experience', 'qualifications', 'review_note', 'reviewed_at']);
        });
    }
};
