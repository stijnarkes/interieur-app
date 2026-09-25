<?php

namespace Tests\Feature;

use App\Support\QuizImageManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt een regressie: een geüploade PNG met een doorzichtige achtergrond kreeg bij het opslaan
 * (en bij het los verkleinen) een zwarte achtergrond, omdat GD een nieuw canvas standaard opaak
 * zwart initialiseert en doorzichtige pixels er zonder imagealphablending(false)/imagesavealpha(true)
 * tegenaan "blendt" in plaats van overneemt. Zie QuizImageManifest::storeAtPath()/downscale().
 */
class QuizImageManifestTransparencyTest extends TestCase
{
    use RefreshDatabase;

    private function transparentPngBytes(int $size = 2000): string
    {
        $image = imagecreatetruecolor($size, $size);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    #[Test]
    public function een_doorzichtige_png_blijft_doorzichtig_na_opslaan(): void
    {
        Storage::fake('public');
        Storage::fake('quiz_images');

        Storage::disk('public')->put('uploads/transparant.png', $this->transparentPngBytes());

        QuizImageManifest::storeAtPath('images/interior/test/transparant.webp', 'uploads/transparant.png');

        $webp = Storage::disk('quiz_images')->get('images/interior/test/transparant.webp');
        $decoded = imagecreatefromstring($webp);
        $colors = imagecolorsforindex($decoded, imagecolorat($decoded, 5, 5));
        imagedestroy($decoded);

        $this->assertGreaterThan(100, $colors['alpha'], 'Achtergrond zou (bijna) volledig doorzichtig moeten blijven, niet zwart.');
    }

    #[Test]
    public function een_doorzichtige_png_blijft_doorzichtig_na_los_verkleinen(): void
    {
        Storage::fake('quiz_images');
        Storage::disk('quiz_images')->put('images/interior/test/groot.webp', (function () {
            $image = imagecreatetruecolor(2000, 2000);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $transparent);
            ob_start();
            imagewebp($image, null, 85);
            $bytes = ob_get_clean();
            imagedestroy($image);

            return $bytes;
        })());

        QuizImageManifest::resizeInPlace('images/interior/test/groot.webp', 800);

        $webp = Storage::disk('quiz_images')->get('images/interior/test/groot.webp');
        $decoded = imagecreatefromstring($webp);
        $colors = imagecolorsforindex($decoded, imagecolorat($decoded, 5, 5));
        imagedestroy($decoded);

        $this->assertGreaterThan(100, $colors['alpha'], 'Achtergrond zou (bijna) volledig doorzichtig moeten blijven na verkleinen, niet zwart.');
    }
}
