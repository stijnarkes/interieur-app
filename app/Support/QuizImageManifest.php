<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Beheert de vaste-slot-afbeeldingen die geen eigen database-rij nodig hebben: de
 * startscherm-foto (1 slot) en de overgangsschermfoto's (1 slot per sectie uit
 * QuizStructure::SECTIONS) — samen `pageSections()`, beheerd op SitePhotosPage — en de
 * sfeerfoto's (6 vaste slots, één per woonstijl) — `atmosphereSections()`, gebruikt door
 * MigrateQuizImages. Het admin-beheer van de sfeerfoto's zelf loopt sinds de invoering van
 * StyleProfilesPage via het `hero_image`-uploadveld daar (per stijl, samen met de rest van de
 * stijlinhoud) — er is geen los beheerscherm meer voor. De 66 stap-foto's per antwoordoptie
 * staan ook niet hier: die worden per rij beheerd via de database
 * (QuizOption, zie QuizOptionsPage) omdat een admin ze inhoudelijk moet kunnen bewerken
 * (titel/stijl/actief), niet alleen de afbeelding kunnen vervangen.
 *
 * Alle daadwerkelijke opslag loopt via de disk in `config('filesystems.quiz_images_disk')`
 * (default de lokale `quiz_images`-disk, die public/ als root heeft — exact het gedrag van
 * vóór deze disk bestond). Op Laravel Cloud kan die op de `s3`-disk gezet worden zodra er
 * Object Storage aan de omgeving hangt: containers zijn daar wegwerpbaar, dus een rechtstreeks
 * op de lokale schijf geschreven upload overleeft geen volgende deploy — persistente storage
 * lost dat structureel op, zonder dat lokaal ontwikkelen iets van AWS hoeft te weten.
 */
class QuizImageManifest
{
    /** Startscherm- en overgangsfoto's — beheerd op een eigen paginatje (zie SitePhotosPage). */
    public static function pageSections(): array
    {
        return [self::heroSection(), self::transitionsSection()];
    }

    /**
     * Sfeerfoto's op de resultaatpagina — admin-beheer loopt via StyleProfilesPage
     * (`hero_image`-uploadveld per stijl); deze methode voedt alleen nog MigrateQuizImages.
     */
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
        // Rechtstreeks uit QuizStructure::STYLES opgebouwd (i.p.v. een eigen, parallelle lijst
        // stijlnamen/bestandsnamen) zodat een woonstijl toevoegen/hernoemen daar voortaan de
        // enige plek is die hoeft te veranderen.
        return [
            'heading' => "Sfeerfoto's op de resultaatpagina",
            'folder' => 'atmosphere',
            'slots' => array_map(static fn (string $key, string $label): array => [
                'filename' => QuizStructure::styleSlug($key).'.webp',
                'style' => $label,
                'label' => "Complete {$label}-woonkamer",
            ], array_keys(QuizStructure::styleOptions()), array_values(QuizStructure::styleOptions())),
        ];
    }

    protected static function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.quiz_images_disk'));
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
     * Cache per request voor de vaste-slot-foto's (startscherm/overgangen/sfeerfoto's) — die
     * hebben, anders dan QuizOption/QuizMaterial, geen eigen databaserij om `has_image` op bij
     * te houden. Zonder deze cache controleert zowel de "X / Y geüpload"-teller als de
     * weergave van elke foto apart, dus twee keer, dezelfde bestanden op de disk. De cache
     * leeft alleen binnen één PHP-request (statische property overleeft geen requests in
     * PHP-FPM), dus kan nooit een verouderd resultaat aan een volgend paginabezoek doorgeven.
     *
     * @var array<string, int|false>|null false = bestaat niet, int = laatst-gewijzigd-tijdstip
     */
    protected static ?array $mtimeCache = null;

    protected static function mtime(string $key): int|false
    {
        self::$mtimeCache ??= [];

        return self::$mtimeCache[$key] ??= (self::disk()->exists($key) ? self::disk()->lastModified($key) : false);
    }

    public static function existsAtPath(string $relativePath): bool
    {
        return self::mtime(ltrim($relativePath, '/')) !== false;
    }

    public static function urlForPath(string $relativePath): ?string
    {
        $key = ltrim($relativePath, '/');
        $mtime = self::mtime($key);

        if ($mtime === false) {
            return null;
        }

        return self::buildUrl($key).'?v='.$mtime;
    }

    /**
     * Zoals urlForPath(), maar geeft altijd een URL terug — ook als het bestand (nog) niet
     * bestaat. Gebruikt door QuizOption/QuizMaterial::publicImageUrl() voor de klant-quiz: die
     * vertrouwt bewust op een 404 van een niet-bestaande foto om netjes op de placeholder terug
     * te vallen (zie optionCard.js), dus daar mag dit nooit null opleveren zoals urlForPath()
     * bij een ontbrekende admin-thumbnail wel doet.
     */
    public static function publicUrlForPath(string $relativePath): string
    {
        $key = ltrim($relativePath, '/');
        $mtime = self::mtime($key);

        return self::buildUrl($key).($mtime !== false ? '?v='.$mtime : '');
    }

    /**
     * Zoals publicUrlForPath(), maar doet nooit een disk-aanroep om te bepalen of het bestand
     * bestaat en welk moment als cache-buster dient — de aanroeper geeft dat al mee (bv. via de
     * `has_image`-kolom en `updated_at` op QuizOption/QuizMaterial, die toch al geladen zijn).
     * Cruciaal op S3/R2: exists()/lastModified() zijn daar netwerkverzoeken, geen snelle
     * bestandssysteem-checks zoals lokaal — een los verzoek per rij liep bij tientallen opties in
     * quiz-config-responses op tot een timeout. Zie QuizOption/QuizMaterial::publicImageUrl().
     */
    public static function urlForKnownPath(string $relativePath, ?int $cacheBuster): string
    {
        $key = ltrim($relativePath, '/');

        return self::buildUrl($key).($cacheBuster !== null ? '?v='.$cacheBuster : '');
    }

    /**
     * Voor de lokale `quiz_images`-disk bewust root-relatief (`/images/...`) i.p.v. de
     * APP_URL-voorziene absolute URL die Storage::url() zou geven: APP_URL staat lokaal vaak niet
     * gelijk aan het adres waarop de site daadwerkelijk draait (Herd's eigen `.test`-domein, een
     * andere poort via `php -S`/`artisan serve`, …) — een root-relatief pad lost zichzelf altijd
     * op t.o.v. de pagina die 'm aanvraagt en is dus origin-onafhankelijk, precies zoals vóór deze
     * disk-abstractie bestond. Alleen bij een echte, andere-origin-disk (S3) is een absolute URL
     * nodig — die bouwt Storage::url() correct op basis van bucket/region/CDN-config.
     */
    protected static function buildUrl(string $key): string
    {
        if (config('filesystems.quiz_images_disk') === 'quiz_images') {
            return '/'.$key;
        }

        return self::disk()->url($key);
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
    public static function store(string $folder, string $filename, string $uploadedDiskPath, int $maxWidth = 1600): void
    {
        self::storeAtPath("images/interior/{$folder}/{$filename}", $uploadedDiskPath, $maxWidth);
    }

    public static function delete(string $folder, string $filename): void
    {
        self::deleteAtPath("images/interior/{$folder}/{$filename}");
    }

    /**
     * $maxWidth is bewust een parameter, geen vaste 1600: de grote overgangs-/sfeerfoto's
     * (ManagesImageSlots) worden bijna schermvullend getoond en hebben die breedte nodig, maar
     * antwoordoptie-/materiaalfoto's (QuizOption/QuizMaterial) tonen altijd als klein kaartje —
     * zie storeImage() op die modellen voor de kleinere waarde die zij doorgeven.
     */
    public static function storeAtPath(string $relativePath, string $uploadedDiskPath, int $maxWidth = 1600): void
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('Deze server ondersteunt geen WebP-conversie (GD mist WebP-support).');
        }

        // Filament's FileUpload zet de zojuist geüploade brondatei altijd tijdelijk op de
        // standaard "public"-disk (storage/app/public) — dat is los van waar de definitieve
        // quizfoto's blijven (self::disk()) en leeft maar heel even, binnen dit ene request.
        $uploadDisk = Storage::disk('public');

        if (! $uploadDisk->exists($uploadedDiskPath)) {
            throw new RuntimeException('Geüpload bestand niet gevonden.');
        }

        $contents = $uploadDisk->get($uploadedDiskPath);
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException('Dit bestand kon niet als afbeelding worden gelezen.');
        }

        self::downscale($image, $maxWidth);

        ob_start();
        imagewebp($image, null, 85);
        $webp = ob_get_clean();
        imagedestroy($image);

        $key = ltrim($relativePath, '/');
        // Geen expliciete 'public'-visibility hier: sommige S3-compatibele providers (o.a.
        // Cloudflare R2, dat Laravel Cloud's Object Storage aanbiedt) regelen zichtbaarheid op
        // bucketniveau en wijzen een per-object ACL-verzoek af met een NotImplemented-fout. De
        // "quiz_images"-disk zet 'visibility' => 'public' al op diskniveau, dus lokaal blijft dit
        // ongewijzigd; op S3 bepaalt de bucketinstelling het.
        self::disk()->put($key, $webp);
        $uploadDisk->delete($uploadedDiskPath);
        unset(self::$mtimeCache[$key]);
    }

    /**
     * Verkleint een al opgeslagen bestand achteraf, zonder tussenkomst van een nieuwe upload —
     * gebruikt door de migratie die de bestaande antwoordoptie-/materiaalfoto's terugbrengt van
     * de oude 1600px-master naar de kleinere kaartjesgrootte. Doet niets als het bestand al
     * kleiner is dan $maxWidth (voorkomt een nutteloze, kwaliteitsverlagende her-encode).
     */
    public static function resizeInPlace(string $relativePath, int $maxWidth): void
    {
        $key = ltrim($relativePath, '/');

        if (! self::disk()->exists($key)) {
            return;
        }

        $image = @imagecreatefromstring(self::disk()->get($key));

        if ($image === false) {
            return;
        }

        if (! self::downscale($image, $maxWidth)) {
            imagedestroy($image);

            return;
        }

        ob_start();
        imagewebp($image, null, 85);
        $webp = ob_get_clean();
        imagedestroy($image);

        self::disk()->put($key, $webp);
        unset(self::$mtimeCache[$key]);
    }

    public static function deleteAtPath(string $relativePath): void
    {
        $key = ltrim($relativePath, '/');

        self::disk()->delete($key);
        unset(self::$mtimeCache[$key]);
    }

    /**
     * Ruwe bytes van een quizfoto, voor het embedden in het PDF-resultaat (zie
     * resources/views/pdf/quiz-result.blade.php) i.p.v. rechtstreeks een lokaal bestand te
     * lezen — dat laatste bestaat niet meer zodra de disk S3 is. Snapt zowel het oude
     * root-relatieve pad (bestaande inzendingen, bv. "/images/interior/floors/japandi.webp")
     * als een volledige URL (nieuwe inzendingen, zie QuizOption::publicImageUrl()). Geeft altijd
     * null terug bij een onbekende/niet-bestaande sleutel — de host in een meegestuurde URL doet
     * er niet toe, er wordt hoe dan ook alleen van de eigen, geconfigureerde disk gelezen.
     */
    public static function contentsFor(string $pathOrUrl): ?string
    {
        $key = self::keyFor($pathOrUrl);

        if (! $key) {
            return null;
        }

        try {
            // Rechtstreeks get() i.p.v. eerst exists() te checken: op een netwerkschijf (S3) is
            // dat één aanroep i.p.v. twee. get() gooit zelf al een uitzondering op een
            // niet-bestaand bestand, wat hieronder hetzelfde "geen afbeelding"-resultaat oplevert.
            return self::disk()->get($key);
        } catch (\Throwable) {
            // Flysystem gooit (i.p.v. false/null terug te geven) op zowel een ontbrekend bestand
            // als bv. een pad-traversal-poging ("../") in de sleutel — dit is een low-level
            // PDF-hulpmethode voor eigen, bekende paden, geen publieke invoervalidatie, dus elke
            // fout hier resulteert gewoon in "geen afbeelding".
            return null;
        }
    }

    /**
     * Laatst-gewijzigd-tijdstip van een quizfoto — een goedkope metadata-aanroep (geen download),
     * gebruikt door PdfImageResolver als cache-sleutel zodat een nieuwe upload vanzelf een nieuwe
     * cache-sleutel krijgt, zonder dat iets de cache handmatig hoeft te legen bij een upload.
     */
    public static function lastModifiedFor(string $pathOrUrl): ?int
    {
        $key = self::keyFor($pathOrUrl);

        if (! $key) {
            return null;
        }

        try {
            return self::disk()->lastModified($key);
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function keyFor(string $pathOrUrl): ?string
    {
        $withoutQuery = explode('?', $pathOrUrl, 2)[0];

        if (! str_starts_with($withoutQuery, 'http://') && ! str_starts_with($withoutQuery, 'https://')) {
            $key = ltrim($withoutQuery, '/');

            return $key !== '' ? $key : null;
        }

        $path = parse_url($withoutQuery, PHP_URL_PATH) ?: '';
        $basePath = parse_url((string) self::disk()->url(''), PHP_URL_PATH) ?: '';

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $key = ltrim($path, '/');

        return $key !== '' ? $key : null;
    }

    protected static function downscale(&$image, int $maxWidth): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth) {
            return false;
        }

        $newWidth = $maxWidth;
        $newHeight = (int) round($height * ($maxWidth / $width));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;

        return true;
    }
}
