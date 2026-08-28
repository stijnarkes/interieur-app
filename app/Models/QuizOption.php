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

        return $path ? asset(ltrim($path, '/')).'?v='.($this->updated_at?->timestamp ?? 0) : null;
    }

    public function storeImage(string $uploadedDiskPath): void
    {
        QuizImageManifest::storeAtPath(ltrim((string) $this->resolvedImagePath(), '/'), $uploadedDiskPath);
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
