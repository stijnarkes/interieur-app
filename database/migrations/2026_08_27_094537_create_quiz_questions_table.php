<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vervangt de tot nu toe hardcoded vragenlijst (App\Support\QuizStructure) door
     * admin-beheerbare rijen — nodig om vragen te kunnen herordenen en nieuwe vragen toe te
     * voegen via de admin. `sort_order` is alleen betekenisvol *binnen* een sectie (niet
     * over secties heen); de twee secties zelf (Kleur & materiaal / Meubels & accessoires)
     * blijven vast, zie SECTION_RANK in QuizStructure.
     */
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question_key')->unique();
            $table->string('section');
            $table->string('title');
            $table->string('folder')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('quiz_questions')->insert(array_map(
            fn (array $row): array => [...$row, 'created_at' => $now, 'updated_at' => $now],
            [
                ['question_key' => 'floor', 'section' => 'materials-colors', 'title' => 'Welke vloer spreekt jou het meeste aan?', 'folder' => 'floors', 'sort_order' => 10],
                ['question_key' => 'wallColor', 'section' => 'materials-colors', 'title' => 'Welke wandkleur past het beste bij jou?', 'folder' => 'walls', 'sort_order' => 20],
                ['question_key' => 'wallFinish', 'section' => 'materials-colors', 'title' => 'Welke wandafwerking spreekt jou het meeste aan?', 'folder' => 'wall-finishes', 'sort_order' => 30],
                ['question_key' => 'sofaMaterial', 'section' => 'materials-colors', 'title' => 'Welke kleur en stof spreekt jou het meeste aan?', 'folder' => 'sofa-materials', 'sort_order' => 40],
                ['question_key' => 'sofaModel', 'section' => 'objects', 'title' => 'Welke bank zou jij het liefst in je woonkamer zetten?', 'folder' => 'sofas', 'sort_order' => 10],
                ['question_key' => 'coffeeTable', 'section' => 'objects', 'title' => 'Welke salontafel past het beste bij jouw smaak?', 'folder' => 'coffee-tables', 'sort_order' => 20],
                ['question_key' => 'diningTable', 'section' => 'objects', 'title' => 'Welke eettafel zou jij kiezen?', 'folder' => 'dining-tables', 'sort_order' => 30],
                ['question_key' => 'diningChair', 'section' => 'objects', 'title' => 'Welke eetkamerstoel spreekt jou het meeste aan?', 'folder' => 'dining-chairs', 'sort_order' => 40],
                ['question_key' => 'lighting', 'section' => 'objects', 'title' => 'Welke verlichting past het beste bij jouw interieur?', 'folder' => 'lighting', 'sort_order' => 50],
                ['question_key' => 'rug', 'section' => 'objects', 'title' => 'Welke stijl vloerkleed spreekt jou het meeste aan?', 'folder' => 'rugs', 'sort_order' => 60],
                ['question_key' => 'cabinet', 'section' => 'objects', 'title' => 'Welke kast of dressoir zou jij kiezen?', 'folder' => 'cabinets', 'sort_order' => 70],
            ]
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
