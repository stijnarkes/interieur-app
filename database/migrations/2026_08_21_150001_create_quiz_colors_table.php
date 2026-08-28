<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_colors', function (Blueprint $table) {
            $table->id();
            $table->string('color_slug')->unique();
            $table->string('title');
            $table->string('color_hex', 7);
            $table->string('color_family')->nullable();
            $table->string('temperature')->nullable();
            $table->string('brightness')->nullable();
            $table->string('saturation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_colors');
    }
};
