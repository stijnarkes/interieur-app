<?php

namespace Tests\Feature;

use App\Models\PartnerComparison;
use App\Models\PartnerLink;
use App\Models\QuizResult;
use App\Models\StyleProfile;
use App\Models\User;
use App\Support\PartnerToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Dekt de admin-only PDF-weergave van een gezamenlijk resultaat (zie PartnerLinkPdfController). */
class PartnerLinkPdfControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeLink(): PartnerLink
    {
        StyleProfile::create(['style_key' => 'japandi', 'label' => 'Japandi', 'slug' => 'japandi']);

        $result = QuizResult::create([
            'uuid' => (string) Str::uuid(), 'answers' => [], 'style_scores' => [], 'primary_style' => 'japandi',
        ]);

        return PartnerLink::create([
            'initiator_quiz_result_id' => $result->id,
            'initiator_snapshot' => ['primary_style' => 'japandi'],
            'partner_snapshot' => ['primary_style' => 'japandi'],
            'invite_token_hash' => PartnerToken::hash('token'),
            'invite_token_encrypted' => encrypt('token'),
            'invite_expires_at' => now()->addDays(30),
        ]);
    }

    #[Test]
    public function een_niet_ingelogde_bezoeker_krijgt_geen_toegang(): void
    {
        $link = $this->makeLink();

        $this->get(route('admin.partner-links.pdf', $link))->assertRedirect();
    }

    #[Test]
    public function zonder_gereedstaande_vergelijking_geeft_de_pdf_route_een_404(): void
    {
        $link = $this->makeLink();

        $this->actingAs($this->admin())
            ->get(route('admin.partner-links.pdf', $link))
            ->assertNotFound();
    }

    #[Test]
    public function met_een_gereedstaande_vergelijking_toont_de_admin_de_pdf(): void
    {
        Storage::fake(config('filesystems.quiz_pdfs_disk'));
        $link = $this->makeLink();
        PartnerComparison::create([
            'partner_link_id' => $link->id,
            'algorithm_version' => '1.0',
            'status' => PartnerComparison::STATUS_READY,
            'facts' => ['similarities' => [], 'differences' => []],
            'suggestions' => ['title' => 'Test'],
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.partner-links.pdf', $link))
            ->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }
}
