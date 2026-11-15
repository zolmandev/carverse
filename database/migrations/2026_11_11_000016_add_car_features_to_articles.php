<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $t) {
            $t->boolean('is_car_feature')->default(false)->index();
            $t->boolean('is_demo_feature')->default(false);
            $t->string('feature_vehicle', 160)->nullable();
            $t->unsignedSmallInteger('feature_year')->nullable();
            $t->string('feature_engine', 160)->nullable();
            $t->string('feature_transmission', 100)->nullable();
            $t->string('feature_location', 120)->nullable();
            $t->string('feature_credit', 160)->nullable();
            $t->text('feature_modifications')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $t) => $t->dropColumn(['is_car_feature', 'is_demo_feature', 'feature_vehicle', 'feature_year', 'feature_engine', 'feature_transmission', 'feature_location', 'feature_credit', 'feature_modifications']));
    }
};
