<?php

namespace App\Models;

use App\Support\QuizImageManifest;
use Illuminate\Database\Eloquent\Model;

class QuizMaterial extends Model
{
    protected $fillable = [
        'style_key',
        'name',
        'filename',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function relativePath(): string
    {
        return "images/interior/materials/{$this->filename}";
    }

    /** Leest de `has_image`-kolom i.p.v. live de schijf te controleren — zie QuizOption::hasImage(). */
    public function hasImage(): bool
    {
        return (bool) $this->has_image;
    }

    public function thumbnailUrl(): ?string
    {
        if (! $this->has_image) {
            return null;
        }

        return QuizImageManifest::urlForKnownPath($this->relativePath(), $this->updated_at?->timestamp);
    }

    public function imagePath(): string
    {
        return "/{$this->relativePath()}";
    }

    /**
     * Zoals thumbnailUrl(), maar geeft altijd een URL terug — voor de klant-quiz (zie
     * QuizConfigController), die op een 404 van een niet-bestaande foto vertrouwt om netjes op
     * de placeholder terug te vallen (materialsSection.js).
     */
    public function publicImageUrl(): string
    {
        return QuizImageManifest::urlForKnownPath(
            $this->relativePath(),
            $this->has_image ? $this->updated_at?->timestamp : null,
        );
    }

    /** Zie QuizOption::storeImage() — zelfde reden voor de kleinere maat. */
    public function storeImage(string $uploadedDiskPath): void
    {
        QuizImageManifest::storeAtPath($this->relativePath(), $uploadedDiskPath, 800);
        $this->forceFill(['has_image' => true])->save();
    }

    public function deleteImage(): void
    {
        QuizImageManifest::deleteAtPath($this->relativePath());
        $this->forceFill(['has_image' => false])->save();
    }
}
