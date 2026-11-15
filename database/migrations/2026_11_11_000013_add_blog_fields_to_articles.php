<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $t) {
            $t->string('slug')->nullable()->unique();
            $t->text('excerpt')->nullable();
            $t->string('category', 80)->default('Guides')->index();
        });
        DB::table('articles')->orderBy('id')->each(function ($a) {
            DB::table('articles')->where('id', $a->id)->update(['slug' => (Str::slug($a->title) ?: 'article').'-'.$a->id]);
        });
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $t) => $t->dropColumn(['slug', 'excerpt', 'category']));
    }
};
