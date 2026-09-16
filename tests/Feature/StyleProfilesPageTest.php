<?php

namespace Tests\Feature;

use App\Filament\Pages\StyleProfilesPage;
use App\Models\StyleProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt een regressie: de materialenfoto-upload op deze pagina bleek nooit iets op te slaan,
 * ongeacht wat er geüpload werd — `dehydrated(false)` op het FileUpload-veld haalde de geüploade
 * waarde altijd uit de formulierdata vóórdat de action 'm te zien kreeg (zie
 * QuizOptionsPage::editOptionAction() voor het correcte patroon: `dehydrated(fn ($state) =>
 * filled($state))`, dat alleen dehydrateert als er ook echt iets geüpload is). De sfeerfoto-upload
 * die deze regressie ooit ook trof, is intussen zelf uitgefaseerd (zie StyleProfilesPage).
 */
class StyleProfilesPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function een_geuploade_materialenfoto_wordt_daadwerkelijk_opgeslagen(): void
    {
        Storage::fake('public');
        Storage::fake('quiz_images');

        $admin = User::factory()->create(['is_admin' => true]);
        $profile = StyleProfile::create([
            'style_key' => 'japandi', 'label' => 'Japandi', 'slug' => 'japandi',
        ]);

        Livewire::actingAs($admin)
            ->test(StyleProfilesPage::class)
            ->callAction('editProfile', data: [
                'label' => 'Japandi',
                'materials_image_upload' => UploadedFile::fake()->image('materialen.jpg'),
            ], arguments: ['profileId' => $profile->id])
            ->assertHasNoActionErrors();

        $profile->refresh();
        $this->assertNotNull($profile->materials_image);
        $this->assertTrue(Storage::disk('quiz_images')->exists(ltrim($profile->materials_image, '/')));
    }
}
