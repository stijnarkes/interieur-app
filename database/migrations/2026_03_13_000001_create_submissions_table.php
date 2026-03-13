<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('style');
            $table->string('mood_words')->nullable();
            $table->string('colors')->nullable();
            $table->text('note')->nullable();
            $table->boolean('has_room_photo')->default(false);
            $table->string('room_photo_path')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->boolean('email_opt_in')->default(false);
            $table->string('result_id')->nullable()->unique();
            $table->boolean('result_generated')->default(false);
            $table->json('advice_bullets')->nullable();
            $table->json('palette')->nullable();
            $table->json('materials')->nullable();
            $table->json('layout_tips')->nullable();
            $table->json('product_ideas')->nullable();
            $table->boolean('moodboard_generated')->default(false);
            $table->boolean('room_preview_generated')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
