<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $t) {
            $t->string('featured_image_path')->nullable();
            $t->string('featured_image_alt', 250)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $t) => $t->dropColumn(['featured_image_path', 'featured_image_alt']));
    }
};
