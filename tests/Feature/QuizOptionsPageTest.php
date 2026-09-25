<?php

namespace Tests\Feature;

use App\Filament\Pages\QuizOptionsPage;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Dekt het slepen van antwoordopties binnen een vraag (zie QuizOptionsPage::reorderOptions()). */
class QuizOptionsPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeOption(string $questionKey, string $slug, int $sortOrder): QuizOption
    {
        return QuizOption::create([
            'question_id' => $questionKey, 'style_key' => 'japandi', 'option_slug' => $slug,
            'primary_style' => 'japandi', 'title' => $slug, 'sort_order' => $sortOrder,
            'is_active' => true, 'has_image' => false,
        ]);
    }

    #[Test]
    public function slepen_slaat_de_nieuwe_volgorde_van_opties_op(): void
    {
        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);

        $first = $this->makeOption('vloer', 'eiken', 10);
        $second = $this->makeOption('vloer', 'grenen', 20);
        $third = $this->makeOption('vloer', 'notenhout', 30);

        Livewire::actingAs($this->admin())
            ->test(QuizOptionsPage::class)
            ->call('reorderOptions', [(string) $third->id, (string) $first->id, (string) $second->id]);

        $this->assertSame(10, $third->fresh()->sort_order);
        $this->assertSame(20, $first->fresh()->sort_order);
        $this->assertSame(30, $second->fresh()->sort_order);
    }

    #[Test]
    public function nieuwe_opties_komen_achteraan_de_lijst_te_staan(): void
    {
        Storage::fake('public');
        Storage::fake('quiz_images');

        QuizQuestion::create([
            'question_key' => 'vloer', 'section' => 'materials-colors', 'title' => 'Welke vloer?',
            'folder' => null, 'sort_order' => 10, 'max_selections' => 1, 'weight' => 1, 'image_display_mode' => 'contain',
        ]);

        $this->makeOption('vloer', 'eiken', 10);
        $this->makeOption('vloer', 'grenen', 20);

        Livewire::actingAs($this->admin())
            ->test(QuizOptionsPage::class)
            ->mountAction('createOption', arguments: ['questionId' => 'vloer'])
            ->setActionData([
                'title' => 'Notenhout', 'style_keys' => ['japandi'],
                'image' => \Illuminate\Http\UploadedFile::fake()->image('notenhout.jpg'),
                'is_active' => true,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $newest = QuizOption::where('option_slug', 'like', 'vloer-notenhout-%')->firstOrFail();
        $this->assertSame(30, $newest->sort_order);
    }
}
