<?php

use App\Models\QuizMaterial;
use App\Support\QuizImageManifest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zelfde reden als has_image op quiz_options (zie die migratie): de materialenpagina deed voor
 * elk materiaal een live File::exists()/lastModified() (2x per materiaal, plus nog eens
 * File::exists() per materiaal voor de "X / Y geüpload"-teller) — op de trage productieschijf
 * merkbaar in laadtijd. QuizMaterial::storeImage()/deleteImage() houden 'm voortaan actueel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_materials', function (Blueprint $table) {
            $table->boolean('has_image')->default(false)->after('filename');
        });

        QuizMaterial::query()->each(function (QuizMaterial $material): void {
            $material->forceFill(['has_image' => QuizImageManifest::existsAtPath($material->relativePath())])->save();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_materials', function (Blueprint $table) {
            $table->dropColumn('has_image');
        });
    }
};
