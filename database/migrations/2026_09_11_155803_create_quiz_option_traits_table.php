<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Koppeling optie -> trait met een gewicht, zelfde vorm als quiz_option_styles. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_option_traits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained('quiz_options')->cascadeOnDelete();
            $table->foreignId('trait_id')->constrained('quiz_traits')->cascadeOnDelete();
            $table->unsignedTinyInteger('weight')->default(1);
            $table->timestamps();

            $table->unique(['option_id', 'trait_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_option_traits');
    }
};
