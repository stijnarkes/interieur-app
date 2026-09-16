<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Haalt en bewerkt foto's voor het PDF-resultaat (zie resources/views/pdf/quiz-result.blade.php)
 * — en cachet het eindresultaat. Dezelfde ~70 antwoordopties en 6 stijlfoto's komen terug in
 * vrijwel elke PDF; zonder cache haalde de server bij élke aanvraag dezelfde foto's opnieuw op
 * (mogelijk van S3, dus over het netwerk) en verwerkte ze opnieuw met GD. Dat kon een aanvraag zo
 * traag maken dat de verbinding tussen browser en server soms verbrak vóórdat het antwoord
 * terugkwam — de bezoeker zag dan "verzenden mislukt" terwijl de mail server-side alsnog aankwam.
 *
 * De cache-sleutel bevat het laatst-gewijzigd-tijdstip van het bestand (QuizImageManifest::
 * lastModifiedFor(), een goedkope metadata-aanroep — geen download) zodat een nieuwe upload
 * vanzelf een nieuwe sleutel krijgt: nooit een verouderde foto tonen, zonder dat een uploadpad
 * ergens de cache handmatig hoeft te legen.
 */
class PdfImageResolver
{
    private const CACHE_TTL_DAYS = 30;

    /**
     * @param  ?float  $containRatio  breedte/hoogte — vult de foto aan tot deze verhouding (nooit
     *   uitrekken/bijsnijden), alleen nodig voor tegels met een vaste hoogte in de layout.
     */
    public function resolve(?string $path, ?float $containRatio = null): ?string
    {
        if (! $path) {
            return null;
        }

        $mtime = QuizImageManifest::lastModifiedFor($path);

        if ($mtime === null) {
            return null;
        }

        $cacheKey = 'pdf-image:'.md5($path.'|'.($containRatio ?? 'raw')).":{$mtime}";

        return Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function () use ($path, $containRatio) {
            $contents = QuizImageManifest::contentsFor($path);

            if (! $contents) {
                return null;
            }

            if ($containRatio !== null) {
                return 'data:image/webp;base64,'.base64_encode($this->padToContainRatio($contents, $containRatio));
            }

            $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                default => 'image/webp',
            };

            return 'data:'.$mime.';base64,'.base64_encode($contents);
        });
    }

    /**
     * dompdf negeert CSS object-fit volledig — een <img> met een vaste breedte én hoogte wordt dus
     * altijd platgedrukt/uitgerekt i.p.v. slim bijgesneden, zoals een browser wel zou doen.
     * Bijsnijden (object-fit: cover) bleek op zijn beurt regelmatig net het belangrijkste deel van
     * een foto wegsnijden (bv. een hanglamp die van boven wordt afgesneden) — in plaats daarvan
     * wordt de hele foto hier altijd volledig zichtbaar gehouden en, waar nodig, opgevuld met een
     * zachte achtergrondkleur tot de tegelverhouding. Nooit uitrekken, nooit bijsnijden.
     */
    private function padToContainRatio(string $contents, float $targetRatio): string
    {
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return $contents;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $currentRatio = $width / $height;

        // De foto zelf wordt nooit geschaald — alleen het canvas wordt breder of hoger gemaakt
        // dan de foto, zodat de volledige, ongewijzigde foto erin past.
        if ($currentRatio > $targetRatio) {
            $canvasWidth = $width;
            $canvasHeight = (int) round($width / $targetRatio);
        } else {
            $canvasHeight = $height;
            $canvasWidth = (int) round($height * $targetRatio);
        }

        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
        // Zelfde tint als .moodboard-placeholder/--accent-soft in app.css, voor een consistente
        // uitstraling wanneer een foto niet exact de tegelverhouding heeft.
        $background = imagecolorallocate($canvas, 0xF0, 0xE3, 0xD4);
        imagefill($canvas, 0, 0, $background);

        $destX = (int) round(($canvasWidth - $width) / 2);
        $destY = (int) round(($canvasHeight - $height) / 2);
        imagecopy($canvas, $image, $destX, $destY, 0, 0, $width, $height);
        imagedestroy($image);

        ob_start();
        imagewebp($canvas, null, 85);
        $webp = ob_get_clean();
        imagedestroy($canvas);

        return $webp;
    }
}
