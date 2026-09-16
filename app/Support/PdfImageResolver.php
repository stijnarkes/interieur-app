<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Haalt en bewerkt foto's voor het PDF-resultaat (zie resources/views/pdf/quiz-result.blade.php)
 * — en cachet het eindresultaat. Dezelfde ~70 antwoordopties en 6 stijlfoto's komen terug in
 * vrijwel elke PDF; zonder cache haalde de server bij élke aanvraag dezelfde foto's opnieuw op
 * (mogelijk van S3, dus over het netwerk) en verwerkte ze opnieuw met GD.
 *
 * Belangrijker nog dan het ophalen bleek, na echt profileren, dompdf's eigen verwerking van de
 * ingebedde afbeeldingen: het inbedden van ~10 foto's op hun oorspronkelijke resolutie (tot 800px
 * breed, ruim bedoeld voor een kaartje op een scherm) kostte dompdf destijds 7+ seconden — genoeg
 * om de verbinding tussen browser en server te laten verbreken vóórdat het antwoord terugkwam. De
 * bezoeker zag dan "verzenden mislukt" terwijl de mail server-side alsnog aankwam. Een foto in
 * deze PDF toont nooit breder dan een paar honderd punten, dus $maxWidth verkleint 'm hier naar
 * een resolutie die daar behoorlijk boven zit (scherp genoeg) maar dompdf niet meer onnodig
 * belast met pixels die toch nooit zichtbaar worden.
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
     * @param  ?int  $maxWidth  verkleint de foto (na het eventueel aanvullen tot $containRatio)
     *   naar hoogstens deze breedte in pixels — zie class-docblock.
     */
    public function resolve(?string $path, ?float $containRatio = null, ?int $maxWidth = null): ?string
    {
        if (! $path) {
            return null;
        }

        $mtime = QuizImageManifest::lastModifiedFor($path);

        if ($mtime === null) {
            return null;
        }

        $cacheKey = 'pdf-image:'.md5($path.'|'.($containRatio ?? 'raw').'|'.($maxWidth ?? 'full')).":{$mtime}";

        return Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function () use ($path, $containRatio, $maxWidth) {
            $contents = QuizImageManifest::contentsFor($path);

            if (! $contents) {
                return null;
            }

            if ($containRatio === null && $maxWidth === null) {
                $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    default => 'image/webp',
                };

                return 'data:'.$mime.';base64,'.base64_encode($contents);
            }

            return 'data:image/webp;base64,'.base64_encode($this->process($contents, $containRatio, $maxWidth));
        });
    }

    private function process(string $contents, ?float $containRatio, ?int $maxWidth): string
    {
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return $contents;
        }

        if ($containRatio !== null) {
            $image = $this->padToContainRatio($image, $containRatio);
        }

        if ($maxWidth !== null) {
            $image = $this->downscaleToWidth($image, $maxWidth);
        }

        ob_start();
        imagewebp($image, null, 85);
        $webp = ob_get_clean();
        imagedestroy($image);

        return $webp;
    }

    /**
     * dompdf negeert CSS object-fit volledig — een <img> met een vaste breedte én hoogte wordt dus
     * altijd platgedrukt/uitgerekt i.p.v. slim bijgesneden, zoals een browser wel zou doen.
     * Bijsnijden (object-fit: cover) bleek op zijn beurt regelmatig net het belangrijkste deel van
     * een foto wegsnijden (bv. een hanglamp die van boven wordt afgesneden) — in plaats daarvan
     * wordt de hele foto hier altijd volledig zichtbaar gehouden en, waar nodig, opgevuld met een
     * zachte achtergrondkleur tot de tegelverhouding. Nooit uitrekken, nooit bijsnijden.
     *
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function padToContainRatio($image, float $targetRatio)
    {
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

        return $canvas;
    }

    /**
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function downscaleToWidth($image, int $maxWidth)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $newWidth = $maxWidth;
        $newHeight = (int) round($height * ($maxWidth / $width));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }
}
