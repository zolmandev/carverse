<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_brands', function (Blueprint $t) {
            $t->id();
            $t->string('name', 160);
            $t->string('slug', 180)->unique();
            $t->string('logo_asset')->nullable();
            $t->string('logo_path')->nullable();
            $t->string('source')->nullable();
            $t->text('source_url')->nullable();
            $t->boolean('is_active')->default(true)->index();
            $t->timestamps();
        });
        Schema::create('car_models', function (Blueprint $t) {
            $t->id();
            $t->foreignId('car_brand_id')->constrained()->restrictOnDelete();
            $t->string('name', 180);
            $t->string('slug', 220);
            $t->string('source')->nullable();
            $t->text('source_url')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['car_brand_id', 'slug']);
            $t->index(['car_brand_id', 'is_active']);
            $t->index('name');
        });
        Schema::create('car_trims', function (Blueprint $t) {
            $t->id();
            $t->foreignId('car_model_id')->constrained()->restrictOnDelete();
            $t->string('name', 255);
            $t->string('source_key', 64);
            $t->unsignedSmallInteger('model_year')->nullable();
            $t->string('market', 100)->default('Unspecified');
            $t->string('kind')->default('grade');
            $t->string('source')->nullable();
            $t->text('source_url')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['car_model_id', 'source_key']);
            $t->index(['car_model_id', 'is_active', 'model_year']);
            $t->index('name');
        });
        Schema::table('communities', function (Blueprint $t) {
            $t->foreignId('car_brand_id')->nullable()->index()->constrained()->restrictOnDelete();
            $t->foreignId('car_model_id')->nullable()->index()->constrained()->restrictOnDelete();
            $t->foreignId('car_trim_id')->nullable()->index()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $t) {
            $t->dropConstrainedForeignId('car_trim_id');
            $t->dropConstrainedForeignId('car_model_id');
            $t->dropConstrainedForeignId('car_brand_id');
        });
        Schema::dropIfExists('car_trims');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('car_brands');
    }
};
