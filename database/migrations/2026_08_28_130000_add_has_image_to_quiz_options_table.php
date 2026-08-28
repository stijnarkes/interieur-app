<?php

use App\Models\QuizOption;
use App\Support\QuizImageManifest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `has_image` vervangt de live bestandscontrole die de admin-pagina "Antwoordopties" eerder
 * voor elke optie deed (66x File::exists()/lastModified() per paginabezoek). Op productie bleek
 * die schijftoegang traag genoeg om de nginx-timeout (20s) te raken — zie QuizOption::hasImage().
 * Deze migratie zet de vlag één keer op basis van de daadwerkelijke bestanden; daarna houden
 * QuizOption::storeImage()/deleteImage() 'm actueel zonder verdere schijftoegang op de hete pad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->boolean('has_image')->default(false)->after('image_path');
        });

        QuizOption::query()->each(function (QuizOption $option): void {
            $path = $option->resolvedImagePath();
            $option->forceFill(['has_image' => $path && QuizImageManifest::existsAtPath($path)])->save();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_options', function (Blueprint $table) {
            $table->dropColumn('has_image');
        });
    }
};
