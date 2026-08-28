<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vervangt de tot nu toe hardcoded sfeerpaletten (resources/js/quiz/paletteData.js) door
     * admin-beheerbare rijen — nodig om paletten en de kleuren daarbinnen te kunnen
     * toevoegen/herordenen/verwijderen. Vervangt daarmee ook de losse-kleuren-admin
     * (QuizColorResource/quiz_colors), die niets meer met de klant-quiz te maken had sinds de
     * kleurvoorkeur-vraag sfeerpaletten kreeg in plaats van losse kleuren.
     */
    public function up(): void
    {
        Schema::create('quiz_palettes', function (Blueprint $table) {
            $table->id();
            $table->string('palette_key')->unique();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_palette_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_palette_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('hex');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        $palettes = [
            'warm-earthy' => [
                'name' => 'Warm & aards',
                'colors' => [
                    ['name' => 'Zand', 'hex' => '#d8c5a7'],
                    ['name' => 'Terracotta', 'hex' => '#b3663f'],
                    ['name' => 'Oker', 'hex' => '#b98a4a'],
                    ['name' => 'Donkerbruin', 'hex' => '#4a3323'],
                ],
            ],
            'soft-light' => [
                'name' => 'Zacht & licht',
                'colors' => [
                    ['name' => 'Warm wit', 'hex' => '#f5f0e6'],
                    ['name' => 'Greige', 'hex' => '#c9bea6'],
                    ['name' => 'Beige', 'hex' => '#cbb188'],
                    ['name' => 'Taupe', 'hex' => '#a8967d'],
                ],
            ],
            'dark-dramatic' => [
                'name' => 'Donker & dramatisch',
                'colors' => [
                    ['name' => 'Antraciet', 'hex' => '#3d3f42'],
                    ['name' => 'Diepblauw', 'hex' => '#2c3a4d'],
                    ['name' => 'Bordeaux', 'hex' => '#5c2530'],
                    ['name' => 'Donkerbruin', 'hex' => '#4a3323'],
                ],
            ],
            'fresh-cool' => [
                'name' => 'Fris & koel',
                'colors' => [
                    ['name' => 'IJsblauw', 'hex' => '#c3d4d9'],
                    ['name' => 'Staalblauw', 'hex' => '#4a6472'],
                    ['name' => 'Koel lichtgrijs', 'hex' => '#cdd3d3'],
                    ['name' => 'Gebroken wit', 'hex' => '#eceff0'],
                ],
            ],
            'green-natural' => [
                'name' => 'Groen & natuurlijk',
                'colors' => [
                    ['name' => 'Mosgroen', 'hex' => '#5f6b4a'],
                    ['name' => 'Olijfgroen', 'hex' => '#74765a'],
                    ['name' => 'Zand', 'hex' => '#d8c5a7'],
                    ['name' => 'Warm wit', 'hex' => '#f5f0e6'],
                ],
            ],
            'rich-refined' => [
                'name' => 'Rijk & verfijnd',
                'colors' => [
                    ['name' => 'Bordeaux', 'hex' => '#5c2530'],
                    ['name' => 'Donkerbruin', 'hex' => '#4a3323'],
                    ['name' => 'Beige', 'hex' => '#cbb188'],
                    ['name' => 'Warm wit', 'hex' => '#f5f0e6'],
                ],
            ],
            'monochrome-sharp' => [
                'name' => 'Monochroom & strak',
                'colors' => [
                    ['name' => 'Lichtgrijs', 'hex' => '#d3d0c9'],
                    ['name' => 'Antraciet', 'hex' => '#3d3f42'],
                    ['name' => 'Warm wit', 'hex' => '#f5f0e6'],
                    ['name' => 'Donkerbruin', 'hex' => '#4a3323'],
                ],
            ],
            'bold-colorful' => [
                'name' => 'Kleurrijk & gedurfd',
                'colors' => [
                    ['name' => 'Terracotta', 'hex' => '#b3663f'],
                    ['name' => 'Oker', 'hex' => '#b98a4a'],
                    ['name' => 'Mosgroen', 'hex' => '#5f6b4a'],
                    ['name' => 'Bordeaux', 'hex' => '#5c2530'],
                ],
            ],
        ];

        $paletteOrder = 0;
        foreach ($palettes as $key => $palette) {
            $paletteOrder += 10;

            $paletteId = DB::table('quiz_palettes')->insertGetId([
                'palette_key' => $key,
                'name' => $palette['name'],
                'sort_order' => $paletteOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $colorOrder = 0;
            foreach ($palette['colors'] as $color) {
                $colorOrder += 10;

                DB::table('quiz_palette_colors')->insert([
                    'quiz_palette_id' => $paletteId,
                    'name' => $color['name'],
                    'hex' => $color['hex'],
                    'sort_order' => $colorOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_palette_colors');
        Schema::dropIfExists('quiz_palettes');
    }
};
