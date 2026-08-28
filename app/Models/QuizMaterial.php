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

    public function hasImage(): bool
    {
        return QuizImageManifest::existsAtPath($this->relativePath());
    }

    public function thumbnailUrl(): ?string
    {
        return QuizImageManifest::urlForPath($this->relativePath());
    }

    public function imagePath(): string
    {
        return "/{$this->relativePath()}";
    }

    public function storeImage(string $uploadedDiskPath): void
    {
        QuizImageManifest::storeAtPath($this->relativePath(), $uploadedDiskPath);
    }

    public function deleteImage(): void
    {
        QuizImageManifest::deleteAtPath($this->relativePath());
    }
}
