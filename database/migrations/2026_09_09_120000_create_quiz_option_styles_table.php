<?php

use App\Models\QuizOption;
use App\Models\QuizOptionStyle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maakt het mogelijk om één antwoordoptie aan meerdere woonstijlen te koppelen (een foto kan bij
 * meerdere stijlen passen), waar `quiz_options.primary_style` voorheen precies 1 stijl toeliet.
 * De pivottabel is losgekoppeld van `style_key` (dat blijft de vaste foto-slot-koppeling bepalen,
 * zie QuizOption::imageFilename()) — dit gaat puur over welke stijl(en) een gekozen optie punten
 * geven in scoring.js. `primary_style` blijft bestaan voor achterwaartse compatibiliteit; nieuwe
 * scoring leest voortaan uit deze pivot. Bestaande opties krijgen hier meteen hun huidige
 * `primary_style` als (enige) gekoppelde stijl, zodat niemands score verandert totdat een admin
 * bewust een 2e stijl toevoegt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_option_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained('quiz_options')->cascadeOnDelete();
            $table->string('style_key');
            $table->timestamps();

            $table->unique(['option_id', 'style_key']);
        });

        QuizOption::query()->each(function (QuizOption $option): void {
            QuizOptionStyle::create([
                'option_id' => $option->id,
                'style_key' => $option->primary_style,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_option_styles');
    }
};
