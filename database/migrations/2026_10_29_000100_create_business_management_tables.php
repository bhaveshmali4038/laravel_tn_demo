<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone_number', 30);
            $table->string('logo_path');
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('branch_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });

        Schema::create('branch_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday')->comment('ISO weekday: Monday=1, Sunday=7');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
            $table->index(['branch_id', 'weekday']);
        });

        Schema::create('branch_date_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_available');
            $table->timestamps();
            $table->unique(['branch_id', 'date']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('branch_date_overrides');
        Schema::dropIfExists('branch_timings');
        Schema::dropIfExists('branch_images');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('businesses');
    }
};
