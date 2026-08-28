<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn([
                'mood_words',
                'colors',
                'note',
                'has_room_photo',
                'room_photo_path',
                'advice_bullets',
                'palette',
                'materials',
                'layout_tips',
                'product_ideas',
                'moodboard_generated',
                'room_preview_generated',
                'moodboard_path',
                'inspiration_path',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('mood_words')->nullable();
            $table->string('colors')->nullable();
            $table->text('note')->nullable();
            $table->boolean('has_room_photo')->default(false);
            $table->string('room_photo_path')->nullable();
            $table->json('advice_bullets')->nullable();
            $table->json('palette')->nullable();
            $table->json('materials')->nullable();
            $table->json('layout_tips')->nullable();
            $table->json('product_ideas')->nullable();
            $table->boolean('moodboard_generated')->default(false);
            $table->boolean('room_preview_generated')->default(false);
            $table->string('moodboard_path')->nullable();
            $table->string('inspiration_path')->nullable();
        });
    }
};
