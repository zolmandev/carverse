<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communities', fn (Blueprint $t) => $t->text('model')->change());
    }

    public function down(): void
    {
        Schema::table('communities', fn (Blueprint $t) => $t->string('model')->change());
    }
};
