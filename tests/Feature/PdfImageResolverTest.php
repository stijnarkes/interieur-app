<?php

namespace Tests\Feature;

use App\Support\PdfImageResolver;
use App\Support\QuizImageManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt de server-side cache die PDF-generatie snel genoeg houdt om synchroon (zonder queue-worker)
 * betrouwbaar te blijven werken — zie QuizLeadController. Zonder deze cache haalt/bewerkt de
 * server dezelfde ~70 antwoordoptie-/stijlfoto's bij élke aanvraag opnieuw.
 */
class PdfImageResolverTest extends TestCase
{
    use RefreshDatabase;

    private function makeWebp(int $width, int $height, array $rgb): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
        ob_start();
        imagewebp($image, null, 85);
        $webp = ob_get_clean();
        imagedestroy($image);

        return $webp;
    }

    #[Test]
    public function een_al_gecachet_resultaat_wordt_teruggegeven_zonder_de_foto_opnieuw_te_verwerken(): void
    {
        Storage::fake(config('filesystems.quiz_images_disk'));
        $disk = Storage::disk(config('filesystems.quiz_images_disk'));
        $path = 'images/interior/test/photo.webp';
        $disk->put($path, $this->makeWebp(200, 200, [10, 20, 30]));

        $mtime = QuizImageManifest::lastModifiedFor("/{$path}");
        $cacheKey = 'pdf-image:'.md5("/{$path}".'|raw').":{$mtime}";
        Cache::put($cacheKey, 'SENTINEL-UIT-CACHE', now()->addMinutes(5));

        $result = (new PdfImageResolver())->resolve("/{$path}");

        $this->assertSame('SENTINEL-UIT-CACHE', $result);
    }

    #[Test]
    public function een_nieuwe_upload_op_hetzelfde_pad_gebruikt_nooit_een_verouderd_gecacht_resultaat(): void
    {
        Storage::fake(config('filesystems.quiz_images_disk'));
        $disk = Storage::disk(config('filesystems.quiz_images_disk'));
        $path = 'images/interior/test/photo.webp';

        $disk->put($path, $this->makeWebp(200, 200, [255, 0, 0]));
        $resolver = new PdfImageResolver();
        $first = $resolver->resolve("/{$path}");

        sleep(1); // garandeert een ander laatst-gewijzigd-tijdstip, zonder de precieze mtime te forceren.
        $disk->put($path, $this->makeWebp(200, 200, [0, 0, 255]));
        $second = $resolver->resolve("/{$path}");

        $this->assertNotSame($first, $second);
    }

    #[Test]
    public function de_foto_wordt_volledig_zichtbaar_gehouden_en_nooit_bijgesneden(): void
    {
        Storage::fake(config('filesystems.quiz_images_disk'));
        $disk = Storage::disk(config('filesystems.quiz_images_disk'));
        $path = 'images/interior/test/portrait.webp';

        // Smalle, hoge testfoto met markeringen boven- en onderaan — die moeten allebei nog
        // aanwezig zijn na het aanpassen aan een brede (2.0) doelverhouding.
        $image = imagecreatetruecolor(200, 500);
        imagefill($image, 0, 0, imagecolorallocate($image, 80, 60, 40));
        $marker = imagecolorallocate($image, 255, 0, 0);
        imagefilledrectangle($image, 0, 0, 199, 4, $marker);
        imagefilledrectangle($image, 0, 495, 199, 499, $marker);
        ob_start();
        imagewebp($image, null, 85);
        $disk->put($path, ob_get_clean());
        imagedestroy($image);

        $dataUri = (new PdfImageResolver())->resolve("/{$path}", 2.0);

        $this->assertNotNull($dataUri);
        [, $base64] = explode(',', $dataUri, 2);
        $decoded = imagecreatefromstring(base64_decode($base64));
        $width = imagesx($decoded);
        $height = imagesy($decoded);

        $this->assertEqualsWithDelta(2.0, $width / $height, 0.05);

        $foundTop = false;
        $foundBottom = false;
        for ($y = 0; $y < $height; $y++) {
            $rgb = imagecolorat($decoded, (int) ($width / 2), $y);
            if ((($rgb >> 16) & 0xFF) > 200 && (($rgb >> 8) & 0xFF) < 50 && ($rgb & 0xFF) < 50) {
                $y < $height / 2 ? $foundTop = true : $foundBottom = true;
            }
        }

        $this->assertTrue($foundTop, 'Bovenmarkering had aanwezig moeten blijven.');
        $this->assertTrue($foundBottom, 'Ondermarkering had aanwezig moeten blijven.');
    }
}
