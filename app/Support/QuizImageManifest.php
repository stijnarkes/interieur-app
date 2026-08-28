<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Beheert de vaste-slot-afbeeldingen die geen eigen database-rij nodig hebben: de
 * startscherm-foto (1 slot) en de overgangsschermfoto's (1 slot per sectie uit
 * QuizStructure::SECTIONS) — samen `pageSections()`, beheerd op SitePhotosPage — en de
 * sfeerfoto's (6 vaste slots, één per woonstijl) — `atmosphereSections()`, beheerd op
 * ImageManagerPage samen met de materialen. De mapnamen/bestandsnamen van de sfeerfoto's
 * mirroren resources/js/quiz/styleProfiles.js. Materialen per stijl staan sinds de invoering
 * van materiaalbeheer (zie ImageManagerPage) in de `quiz_materials`-tabel, niet meer hier — die
 * zijn, anders dan de sfeerfoto's, geen vaste 1-op-1 set meer maar per stijl uitbreidbaar. De 66
 * stap-foto's per antwoordoptie staan ook niet hier: die worden per rij beheerd via de database
 * (QuizOption, zie QuizOptionsPage) omdat een admin ze inhoudelijk moet kunnen bewerken
 * (titel/stijl/actief), niet alleen de afbeelding kunnen vervangen.
 */
class QuizImageManifest
{
    /** Startscherm- en overgangsfoto's — beheerd op een eigen paginatje (zie SitePhotosPage). */
    public static function pageSections(): array
    {
        return [self::heroSection(), self::transitionsSection()];
    }

    /** Sfeerfoto's op de resultaatpagina — beheerd op ImageManagerPage, samen met materialen. */
    public static function atmosphereSections(): array
    {
        return [self::atmosphereSection()];
    }

    protected static function heroSection(): array
    {
        return [
            'heading' => 'Startscherm-foto',
            'folder' => 'hero',
            'slots' => [
                [
                    'filename' => 'startscherm.webp',
                    'style' => 'Startscherm',
                    'label' => 'Foto boven de titel op het startscherm — ideaal formaat 700×350px (2:1)',
                ],
            ],
        ];
    }

    protected static function transitionsSection(): array
    {
        return [
            'heading' => 'Overgangsschermen tussen onderdelen',
            'folder' => 'transitions',
            'slots' => array_map(static fn (array $section): array => [
                'filename' => "{$section['id']}.webp",
                'style' => $section['title'],
                'label' => 'Foto op het overgangsscherm naar dit onderdeel — ideaal formaat circa 2,4:1 (bv. 1600×667px)',
            ], array_values(QuizStructure::SECTIONS)),
        ];
    }

    protected static function atmosphereSection(): array
    {
        $styles = [
            'Japandi', 'Hotel Chique', 'Industrieel',
            'Biophilic / Botanisch', 'Landelijk modern', 'Retro / Vintage',
        ];

        $filenames = [
            'japandi.webp', 'hotel-chique.webp', 'industrial.webp',
            'biophilic.webp', 'modern-country.webp', 'retro-vintage.webp',
        ];

        return [
            'heading' => "Sfeerfoto's op de resultaatpagina",
            'folder' => 'atmosphere',
            'slots' => array_map(static fn (string $style, string $filename): array => [
                'filename' => $filename,
                'style' => $style,
                'label' => "Complete {$style}-woonkamer",
            ], $styles, $filenames),
        ];
    }

    public static function path(string $folder, string $filename): string
    {
        return self::absolutePathFor("images/interior/{$folder}/{$filename}");
    }

    public static function exists(string $folder, string $filename): bool
    {
        return self::existsAtPath("images/interior/{$folder}/{$filename}");
    }

    public static function url(string $folder, string $filename): ?string
    {
        return self::urlForPath("images/interior/{$folder}/{$filename}");
    }

    /**
     * Generieke varianten van path()/exists()/url()/store()/delete() die een volledig relatief
     * pad accepteren (bv. "images/interior/extra/mijn-optie.webp") in plaats van folder+filename.
     * Nodig voor QuizOption-rijen met een eigen, expliciet image_path (zie QuizOption::hasImage()
     * e.a.) — bv. door de admin zelf toegevoegde extra keuzes die geen vaste stijl-slot volgen.
     */
    public static function absolutePathFor(string $relativePath): string
    {
        return public_path(ltrim($relativePath, '/'));
    }

    /**
     * Cache per request voor de vaste-slot-foto's (startscherm/overgangen/sfeerfoto's) — die
     * hebben, anders dan QuizOption/QuizMaterial, geen eigen databaserij om `has_image` op bij
     * te houden. Zonder deze cache controleert zowel de "X / Y geüpload"-teller als de
     * weergave van elke foto apart, dus twee keer, dezelfde bestanden op de schijf. De cache
     * leeft alleen binnen één PHP-request (statische property overleeft geen requests in
     * PHP-FPM), dus kan nooit een verouderd resultaat aan een volgend paginabezoek doorgeven.
     *
     * @var array<string, int|false>|null false = bestaat niet, int = laatst-gewijzigd-tijdstip
     */
    protected static ?array $mtimeCache = null;

    protected static function mtime(string $absolutePath): int|false
    {
        self::$mtimeCache ??= [];

        return self::$mtimeCache[$absolutePath] ??= (File::exists($absolutePath) ? File::lastModified($absolutePath) : false);
    }

    public static function existsAtPath(string $relativePath): bool
    {
        return self::mtime(self::absolutePathFor($relativePath)) !== false;
    }

    public static function urlForPath(string $relativePath): ?string
    {
        $mtime = self::mtime(self::absolutePathFor($relativePath));

        if ($mtime === false) {
            return null;
        }

        return asset(ltrim($relativePath, '/')).'?v='.$mtime;
    }

    public static function totalCount(array $sections): int
    {
        return array_sum(array_map(
            static fn (array $section): int => count($section['slots']),
            $sections
        ));
    }

    public static function uploadedCount(array $sections): int
    {
        $count = 0;

        foreach ($sections as $section) {
            foreach ($section['slots'] as $slot) {
                if (self::exists($section['folder'], $slot['filename'])) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Reads the uploaded file from the given public-disk path, converts it
     * to WebP, and stores it at the exact path the quiz expects.
     */
    public static function store(string $folder, string $filename, string $uploadedDiskPath): void
    {
        self::storeAtPath("images/interior/{$folder}/{$filename}", $uploadedDiskPath);
    }

    public static function delete(string $folder, string $filename): void
    {
        self::deleteAtPath("images/interior/{$folder}/{$filename}");
    }

    public static function storeAtPath(string $relativePath, string $uploadedDiskPath): void
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('Deze server ondersteunt geen WebP-conversie (GD mist WebP-support).');
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($uploadedDiskPath)) {
            throw new RuntimeException('Geüpload bestand niet gevonden.');
        }

        $contents = $disk->get($uploadedDiskPath);
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException('Dit bestand kon niet als afbeelding worden gelezen.');
        }

        self::downscale($image, 1600);

        $target = self::absolutePathFor($relativePath);
        File::ensureDirectoryExists(dirname($target));

        ob_start();
        imagewebp($image, null, 85);
        $webp = ob_get_clean();
        imagedestroy($image);

        File::put($target, $webp);
        $disk->delete($uploadedDiskPath);
        unset(self::$mtimeCache[$target]);
    }

    public static function deleteAtPath(string $relativePath): void
    {
        $target = self::absolutePathFor($relativePath);

        if (File::exists($target)) {
            File::delete($target);
        }

        unset(self::$mtimeCache[$target]);
    }

    protected static function downscale(&$image, int $maxWidth): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth) {
            return;
        }

        $newWidth = $maxWidth;
        $newHeight = (int) round($height * ($maxWidth / $width));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }
}
