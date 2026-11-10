<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fitments', function (Blueprint $t) {
            $t->json('vehicle_details')->nullable();
            $t->json('part_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fitments', fn (Blueprint $t) => $t->dropColumn(['vehicle_details', 'part_details']));
    }
};
