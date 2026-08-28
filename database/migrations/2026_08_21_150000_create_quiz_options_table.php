<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();
            $table->string('question_id');
            $table->string('style_key');
            $table->string('option_slug')->unique();
            $table->string('primary_style');
            $table->string('title');
            $table->string('color_hex', 7)->nullable();
            $table->string('color_family')->nullable();
            $table->string('color_temperature')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('product_name')->nullable();
            $table->string('sku')->nullable();
            $table->string('brand')->nullable();
            $table->string('product_url')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->boolean('showroom_product')->default(false);
            $table->timestamps();

            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_options');
    }
};
