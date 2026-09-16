<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimale, PII-vrije telling voor de partnerfunctie (zie het implementatieplan, sectie 11) — geen
 * tracking-script, geen consent nodig: puur server-side eigen telling van event-namen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_link_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_events');
    }
};
