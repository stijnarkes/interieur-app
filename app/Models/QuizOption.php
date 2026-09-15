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
        'secondary_style',
        'internal_note',
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

    /**
     * @return array<int, string> de 1 of 2 gekoppelde stijl-keys (hoofdstijl + evt. tweede stijl),
     * zie QuizScoringService::compute(). Leeg als er nog geen hoofdstijl is gekoppeld (onvolledige
     * optie — mag dan niet in de klant-quiz verschijnen, zie QuizConfigController).
     */
    public function linkedStyleKeys(): array
    {
        return array_values(array_filter([$this->primary_style, $this->secondary_style]));
    }

    /** @deprecated Vervangen door primary_style/secondary_style — zie linkedStyleKeys(). Blijft staan als historisch archief van de vorige, complexere stijlkoppeling. */
    public function styleLinks()
    {
        return $this->hasMany(QuizOptionStyle::class, 'option_id');
    }

    /** @deprecated Traits/kenmerken-scores zijn buiten scope — zie het implementatieplan "vereenvoudiging woonstijltest". Blijft staan als historisch archief. */
    public function traitLinks()
    {
        return $this->hasMany(QuizOptionTrait::class, 'option_id');
    }

    /** @deprecated Gebruik linkedStyleKeys(). */
    public function styleKeys(): array
    {
        return $this->styleLinks->pluck('style_key')->all();
    }

    /** @deprecated Gewogen punten per stijl bestaan niet meer — elke gekoppelde stijl telt altijd volledig mee. */
    public function stylePoints(): array
    {
        return $this->styleLinks->pluck('points', 'style_key')->all();
    }

    /**
     * @deprecated Gebruik het primary_style/secondary_style-paar rechtstreeks.
     * @param  array<string, int>  $stylePoints  stijl-key => punten (bv. ['japandi' => 3, 'natuurlijk' => 1])
     */
    public function syncStylesWithPoints(array $stylePoints): void
    {
        $this->styleLinks()->whereNotIn('style_key', array_keys($stylePoints))->delete();

        foreach ($stylePoints as $styleKey => $points) {
            $this->styleLinks()->updateOrCreate(['style_key' => $styleKey], ['points' => $points]);
        }
    }

    /** @deprecated Traits/kenmerken-scores zijn buiten scope. */
    public function traitWeights(): array
    {
        return $this->traitLinks->pluck('weight', 'trait_id')->all();
    }

    /**
     * @deprecated Traits/kenmerken-scores zijn buiten scope.
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
