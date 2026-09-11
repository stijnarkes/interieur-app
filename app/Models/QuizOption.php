<?php

namespace App\Models;

use App\Support\QuizImageManifest;
use App\Support\QuizStructure;
use Illuminate\Database\Eloquent\Model;

class QuizOption extends Model
{
    protected $fillable = [
        'question_id',
        'style_key',
        'option_slug',
        'primary_style',
        'title',
        'image_path',
        'color_hex',
        'color_family',
        'color_temperature',
        'is_active',
        'product_name',
        'sku',
        'brand',
        'product_url',
        'price',
        'showroom_product',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'showroom_product' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function styleLinks()
    {
        return $this->hasMany(QuizOptionStyle::class, 'option_id');
    }

    public function traitLinks()
    {
        return $this->hasMany(QuizOptionTrait::class, 'option_id');
    }

    /** @return array<int, string> stijl-keys waar deze optie punten aan geeft (zie QuizScoringService). */
    public function styleKeys(): array
    {
        return $this->styleLinks->pluck('style_key')->all();
    }

    /** @return array<string, int> stijl-key => punten, zie QuizScoringService::compute(). */
    public function stylePoints(): array
    {
        return $this->styleLinks->pluck('points', 'style_key')->all();
    }

    /**
     * @param  array<string, int>  $stylePoints  stijl-key => punten (bv. ['japandi' => 3, 'natuurlijk' => 1])
     */
    public function syncStylesWithPoints(array $stylePoints): void
    {
        $this->styleLinks()->whereNotIn('style_key', array_keys($stylePoints))->delete();

        foreach ($stylePoints as $styleKey => $points) {
            $this->styleLinks()->updateOrCreate(['style_key' => $styleKey], ['points' => $points]);
        }
    }

    /** @return array<string, int> trait-key => gewicht. */
    public function traitWeights(): array
    {
        return $this->traitLinks->pluck('weight', 'trait_id')->all();
    }

    /**
     * @param  array<int, int>  $traitWeights  trait_id => gewicht
     */
    public function syncTraits(array $traitWeights): void
    {
        $this->traitLinks()->whereNotIn('trait_id', array_keys($traitWeights))->delete();

        foreach ($traitWeights as $traitId => $weight) {
            $this->traitLinks()->updateOrCreate(['trait_id' => $traitId], ['weight' => $weight]);
        }
    }

    /** Mapnaam onder public/images/interior/, afgeleid van de vraag (niet bewerkbaar). */
    public function imageFolder(): ?string
    {
        return QuizStructure::folderFor($this->question_id);
    }

    /** Bestandsnaam, afgeleid van de onveranderlijke style_key (niet van de bewerkbare primary_style). */
    public function imageFilename(): string
    {
        return QuizStructure::styleSlug($this->style_key).'.webp';
    }

    /**
     * Het relatieve afbeeldingspad dat de klant-quiz gebruikt. Voor de oorspronkelijke, geseede
     * opties is dat altijd de vaste map/stijl-slug-conventie (image_path staat dan op null). Een
     * door de admin zelf toegevoegde extra optie heeft geen natuurlijke "slot" om die conventie
     * op te baseren en krijgt daarom een eigen, expliciet image_path bij het aanmaken.
     */
    public function resolvedImagePath(): ?string
    {
        if ($this->image_path) {
            return $this->image_path;
        }

        $folder = $this->imageFolder();

        return $folder ? "/images/interior/{$folder}/{$this->imageFilename()}" : null;
    }

    /**
     * Leest bewust de `has_image`-kolom in plaats van live de schijf te controleren: deze
     * methode wordt op de admin-opties-pagina voor alle 66 opties per paginabezoek aangeroepen,
     * en een losse File::exists() per optie bleek in productie traag genoeg om de
     * server-timeout te raken. storeImage()/deleteImage() houden de kolom actueel.
     */
    public function hasImage(): bool
    {
        return (bool) $this->has_image;
    }

    public function thumbnailUrl(): ?string
    {
        if (! $this->has_image) {
            return null;
        }

        $path = $this->resolvedImagePath();

        return $path ? QuizImageManifest::urlForKnownPath($path, $this->updated_at?->timestamp) : null;
    }

    /**
     * Zoals thumbnailUrl(), maar geeft altijd een URL terug — voor de klant-quiz (zie
     * QuizConfigController), die op een 404 van een niet-bestaande foto vertrouwt om netjes op
     * de placeholder terug te vallen (optionCard.js).
     */
    public function publicImageUrl(): string
    {
        return QuizImageManifest::urlForKnownPath(
            (string) $this->resolvedImagePath(),
            $this->has_image ? $this->updated_at?->timestamp : null,
        );
    }

    /** 800px is ruim genoeg voor een scherp kaartje op retina-schermen — deze foto's tonen
     *  nergens groter dan een paar honderd pixels (quizkaartje, PDF-moodboard, adminlijst). */
    public function storeImage(string $uploadedDiskPath): void
    {
        QuizImageManifest::storeAtPath(ltrim((string) $this->resolvedImagePath(), '/'), $uploadedDiskPath, 800);
        $this->forceFill(['has_image' => true])->save();
    }

    public function deleteImage(): void
    {
        if ($path = $this->resolvedImagePath()) {
            QuizImageManifest::deleteAtPath($path);
        }

        $this->forceFill(['has_image' => false])->save();
    }
}
