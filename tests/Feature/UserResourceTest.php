<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Dekt het gebruikersbeheer (zie UserResource) — met name dat een nieuwe gebruiker zelf een
 * wachtwoord instelt via een e-mail i.p.v. dat een beheerder er zelf een voor hen verzint en
 * doorgeeft.
 */
class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    #[Test]
    public function een_gewone_gebruiker_zonder_rechten_krijgt_geen_toegang(): void
    {
        $this->get(UserResource::getUrl('index'))->assertRedirect();

        $gewoneGebruiker = User::factory()->create(['is_admin' => false]);
        $this->actingAs($gewoneGebruiker)->get(UserResource::getUrl('index'))->assertForbidden();
    }

    #[Test]
    public function het_aanmaken_van_een_gebruiker_toont_geen_wachtwoordveld_en_verstuurt_een_instelmail(): void
    {
        Notification::fake();

        Livewire::actingAs($this->admin())
            ->test(UserResource\Pages\ListUsers::class)
            ->mountTableAction('create')
            ->setTableActionData([
                'name' => 'Nieuwe Collega',
                'email' => 'collega@boer-staphorst.nl',
                'is_admin' => false,
                'can_manage_quiz' => false,
                'can_view_results' => true,
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $nieuweGebruiker = User::where('email', 'collega@boer-staphorst.nl')->firstOrFail();

        $this->assertTrue($nieuweGebruiker->can_view_results);
        $this->assertFalse($nieuweGebruiker->is_admin);
        // Het wachtwoord staat op iets willekeurigs/onbekends — niemand gebruikt dit ooit, de
        // nieuwe gebruiker stelt via de mail hieronder zelf het echte wachtwoord in.
        $this->assertNotNull($nieuweGebruiker->password);

        Notification::assertSentTo($nieuweGebruiker, SetPasswordNotification::class);
    }

    #[Test]
    public function de_instelmail_wordt_niet_op_de_wachtrij_gezet(): void
    {
        // SetPasswordNotification heeft bewust geen ShouldQueue (deze app draait zonder actieve
        // queue-worker, zie UserResource::sendSetPasswordLink()). Notification::fake() zou een
        // toekomstige queue-regressie hier verbergen, dus bewust zonder fake: controleer dat er
        // simpelweg niks in de jobs-tabel belandt.
        Livewire::actingAs($this->admin())
            ->test(UserResource\Pages\ListUsers::class)
            ->mountTableAction('create')
            ->setTableActionData([
                'name' => 'Nieuwe Collega',
                'email' => 'sync-check@boer-staphorst.nl',
                'is_admin' => false,
                'can_manage_quiz' => false,
                'can_view_results' => true,
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseCount('jobs', 0);
    }

    #[Test]
    public function de_instelmail_is_nederlands_en_gaat_over_instellen_niet_over_resetten(): void
    {
        $gebruiker = User::factory()->create(['name' => 'Anna']);

        $mail = (new SetPasswordNotification('https://example.com/stel-wachtwoord-in'))->toMail($gebruiker);

        $this->assertSame('Stel je wachtwoord in', $mail->subject);
        $this->assertStringContainsString('Wachtwoord instellen', $mail->actionText);
        $this->assertStringNotContainsString('reset', mb_strtolower($mail->subject));
        $this->assertStringNotContainsString('reset', mb_strtolower($mail->actionText));
    }
}
