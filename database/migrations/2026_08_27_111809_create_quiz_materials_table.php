<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vervangt de tot nu toe hardcoded materialenlijst (App\Support\QuizImageManifest) door
     * admin-beheerbare rijen — nodig om per woonstijl materialen te kunnen toevoegen/verwijderen.
     * De sfeerfoto's (6 vaste plekken, één per stijl) blijven wél gewoon in QuizImageManifest:
     * die zijn per definitie altijd precies 1-op-1 met een stijl, dus daar is niets aan toe te
     * voegen zolang er geen nieuwe woonstijl bestaat.
     */
    public function up(): void
    {
        Schema::create('quiz_materials', function (Blueprint $table) {
            $table->id();
            $table->string('style_key');
            $table->string('name');
            $table->string('filename');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        $groups = [
            'japandi' => [
                ['filename' => 'japandi-oak.webp', 'name' => 'Naturel eiken'],
                ['filename' => 'japandi-linen.webp', 'name' => 'Linnen'],
                ['filename' => 'japandi-boucle.webp', 'name' => 'Wol / bouclé'],
                ['filename' => 'japandi-ceramic.webp', 'name' => 'Keramiek'],
            ],
            'hotelChique' => [
                ['filename' => 'hotel-chique-marble.webp', 'name' => 'Marmer'],
                ['filename' => 'hotel-chique-brass.webp', 'name' => 'Messing'],
                ['filename' => 'hotel-chique-velvet.webp', 'name' => 'Fluweel'],
                ['filename' => 'hotel-chique-dark-wood.webp', 'name' => 'Donker hout'],
            ],
            'industrial' => [
                ['filename' => 'industrial-concrete.webp', 'name' => 'Beton'],
                ['filename' => 'industrial-metal.webp', 'name' => 'Zwart metaal'],
                ['filename' => 'industrial-dark-wood.webp', 'name' => 'Donker hout'],
                ['filename' => 'industrial-leather.webp', 'name' => 'Leer'],
            ],
            'biophilic' => [
                ['filename' => 'biophilic-rattan.webp', 'name' => 'Rotan'],
                ['filename' => 'biophilic-wood.webp', 'name' => 'Naturel hout'],
                ['filename' => 'biophilic-linen.webp', 'name' => 'Linnen'],
                ['filename' => 'biophilic-stone.webp', 'name' => 'Natuursteen'],
            ],
            'modernCountry' => [
                ['filename' => 'modern-country-wood.webp', 'name' => 'Massief hout'],
                ['filename' => 'modern-country-linen.webp', 'name' => 'Linnen'],
                ['filename' => 'modern-country-wool.webp', 'name' => 'Wol'],
                ['filename' => 'modern-country-stone.webp', 'name' => 'Natuursteen'],
            ],
            'retroVintage' => [
                ['filename' => 'retro-vintage-teak.webp', 'name' => 'Teak / walnoot'],
                ['filename' => 'retro-vintage-velvet.webp', 'name' => 'Velours'],
                ['filename' => 'retro-vintage-glass.webp', 'name' => 'Glas'],
                ['filename' => 'retro-vintage-ceramic.webp', 'name' => 'Keramiek'],
            ],
        ];

        $rows = [];
        foreach ($groups as $styleKey => $materials) {
            foreach ($materials as $index => $material) {
                $rows[] = [
                    'style_key' => $styleKey,
                    'name' => $material['name'],
                    'filename' => $material['filename'],
                    'sort_order' => ($index + 1) * 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('quiz_materials')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_materials');
    }
};
