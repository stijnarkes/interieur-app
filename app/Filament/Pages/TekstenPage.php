<?php

namespace App\Filament\Pages;

use App\Models\QuizTransitionSection;
use App\Models\SiteContent;
use App\Support\QuizStructure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Beheert de stijl-onafhankelijke schermteksten (startscherm, overgangsschermen,
 * resultatenpagina, bevestigingsmail) die tot nu toe hardcoded stonden in welcome.blade.php, de
 * resultaatpagina-JS-componenten en de mail — zie SiteContent/QuizTransitionSection. De
 * stijlspecifieke teksten (introductie, kenmerken, kleuren, materialen, advies) blijven op
 * Stijlprofielen staan, dit gaat er niet over.
 */
class TekstenPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Teksten';

    protected static ?string $title = 'Website- en e-mailteksten';

    protected static ?string $slug = 'teksten';

    protected static ?string $navigationGroup = 'Quizbeheer';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.teksten-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->canManageQuiz() ?? false;
    }

    public function getSiteContent(): SiteContent
    {
        return SiteContent::current();
    }

    /** @return array<int, QuizTransitionSection> in de vaste QuizStructure::SECTIONS-volgorde */
    public function getTransitionSections(): array
    {
        $sections = QuizTransitionSection::query()->get()->keyBy('section_id');

        return collect(array_keys(QuizStructure::SECTIONS))
            ->map(fn (string $id) => $sections->get($id))
            ->filter()
            ->values()
            ->all();
    }

    public function editStartScreenAction(): Action
    {
        return Action::make('editStartScreen')
            ->label('Bewerken')
            ->modalHeading('Startscherm bewerken')
            ->fillForm(fn (): array => $this->getSiteContent()->toArray())
            ->form([
                TextInput::make('start_title')->label('Titel')->required()->maxLength(255),
                Textarea::make('start_intro')->label('Introductietekst')->rows(3)->required(),
                TextInput::make('start_button_label')->label('Knoptekst')->required()->maxLength(255),
                TextInput::make('start_duration_fact')->label('Duur-tekst')->helperText('Bv. "± 3 minuten".')->required()->maxLength(255),
                TextInput::make('start_questions_suffix')->label('Tekst na het aantal vragen')->helperText('Het aantal vragen zelf is altijd actueel; dit is alleen het woord erna, bv. "vragen".')->required()->maxLength(255),
            ])
            ->action(function (array $data): void {
                $this->getSiteContent()->update($data);

                Notification::make()->title('Startscherm bijgewerkt')->success()->send();
            });
    }

    public function editSectionAction(): Action
    {
        return Action::make('editSection')
            ->label('Bewerken')
            ->modalHeading(fn (array $arguments): string => 'Overgangsscherm bewerken: '.(QuizTransitionSection::forSection($arguments['sectionId'])?->title ?? ''))
            ->fillForm(fn (array $arguments): array => $this->findSectionOrFail($arguments['sectionId'])->toArray())
            ->form([
                TextInput::make('title')->label('Titel')->required()->maxLength(255),
                Textarea::make('tagline')->label('Introductietekst')->rows(2)->required(),
                Textarea::make('wrap_up')->label('Afsluitende zin vorige onderdeel')->helperText('Alleen zichtbaar als er een vorig onderdeel is afgerond — laat leeg voor het eerste onderdeel.')->rows(2),
                TextInput::make('cta')->label('Knoptekst')->required()->maxLength(255),
            ])
            ->action(function (array $arguments, array $data): void {
                $this->findSectionOrFail($arguments['sectionId'])->update($data);

                Notification::make()->title('Overgangsscherm bijgewerkt')->success()->send();
            });
    }

    /**
     * `arguments['sectionId']` is de string-slug uit QuizStructure::SECTIONS (bv.
     * "materials-colors"), niet de numerieke primaire sleutel — vandaar de lookup via
     * forSection() i.p.v. find()/findOrFail(), die anders altijd een ModelNotFoundException
     * (en dus een 404) geven omdat de slug nooit een geldige id is.
     */
    private function findSectionOrFail(string $sectionId): QuizTransitionSection
    {
        return QuizTransitionSection::forSection($sectionId) ?? throw new \RuntimeException("Onbekend overgangsscherm: {$sectionId}");
    }

    public function editResultCopyAction(): Action
    {
        return Action::make('editResultCopy')
            ->label('Bewerken')
            ->modalHeading('Resultatenpagina bewerken')
            ->modalWidth('4xl')
            ->fillForm(fn (): array => $this->getSiteContent()->toArray())
            ->form([
                FormSection::make('Titel')
                    ->schema([
                        TextInput::make('result_page_title')->label('Titel boven het resultaat')->required()->maxLength(255),
                    ]),

                FormSection::make('Heldenblok')
                    ->collapsed()
                    ->schema([
                        TextInput::make('hero_eyebrow')->label('Eyebrow-tekst')->required()->maxLength(255),
                        Textarea::make('hero_expectation')->label('Verwachtingstekst')->rows(3)->required(),
                        TextInput::make('hero_primary_label')->label('Label basisstijl')->required()->maxLength(255),
                        TextInput::make('hero_secondary_label')->label('Label invloed')->required()->maxLength(255),
                    ]),

                FormSection::make('Rapport-teaser')
                    ->collapsed()
                    ->schema([
                        TextInput::make('teaser_title')->label('Titel')->required()->maxLength(255),
                        Textarea::make('teaser_intro')->label('Introductietekst')->rows(2)->required(),
                        TextInput::make('teaser_list_intro')->label('Tekst boven het lijstje')->required()->maxLength(255),
                        TagsInput::make('teaser_checklist_items')->label('Lijstje met wat het rapport bevat')->helperText('Enter om een regel toe te voegen.'),
                        TextInput::make('teaser_mock_label')->label('Label op de voorbeeldkaft')->required()->maxLength(255),
                    ]),

                FormSection::make('Aanvraagformulier')
                    ->collapsed()
                    ->schema([
                        TextInput::make('lead_heading')->label('Titel')->required()->maxLength(255),
                        Textarea::make('lead_intro')->label('Introductietekst')->rows(2)->required(),
                        TextInput::make('lead_optin_label')->label('Tekst bij het opt-in-vakje')->required()->maxLength(255),
                        TextInput::make('lead_submit_label')->label('Knoptekst')->required()->maxLength(255),
                        TextInput::make('lead_reassurance')->label('Geruststellende tekst onder de knop')->required()->maxLength(255),
                    ]),

                FormSection::make('Bevestiging na verzenden')
                    ->collapsed()
                    ->schema([
                        TextInput::make('lead_success_title')->label('Titel')->required()->maxLength(255),
                        Textarea::make('lead_success_body')->label('Tekst')->helperText('Gebruik {name} en {email} — die worden automatisch vervangen door de ingevulde naam en het e-mailadres.')->rows(2)->required(),
                        TextInput::make('lead_queued_title')->label('Titel (aanvraag ontvangen, nog niet bevestigd verstuurd)')->helperText('Verschijnt zodra de aanvraag in behandeling is genomen maar het versturen zelf nog niet bevestigd is.')->required()->maxLength(255),
                        Textarea::make('lead_queued_body')->label('Tekst (aanvraag ontvangen)')->rows(2)->required(),
                        TextInput::make('lead_spam_hint')->label('Spam-hint')->required()->maxLength(255),
                        TextInput::make('lead_expect_title')->label('Tekst boven het lijstje')->required()->maxLength(255),
                        TagsInput::make('lead_expect_items')->label('Lijstje met wat de bezoeker kan verwachten')->helperText('Enter om een regel toe te voegen.'),
                        TextInput::make('lead_resend_label')->label('Knoptekst "opnieuw versturen"')->required()->maxLength(255),
                    ]),
            ])
            ->action(function (array $data): void {
                $this->getSiteContent()->update($data);

                Notification::make()->title('Resultatenpagina bijgewerkt')->success()->send();
            });
    }

    public function editEmailCopyAction(): Action
    {
        return Action::make('editEmailCopy')
            ->label('Bewerken')
            ->modalHeading('E-mailtekst bewerken')
            ->fillForm(fn (): array => $this->getSiteContent()->toArray())
            ->form([
                TextInput::make('email_subject')->label('Onderwerp')->required()->maxLength(255),
                TextInput::make('email_header')->label('Header-tekst')->required()->maxLength(255),
                TextInput::make('email_greeting')->label('Aanhef')->helperText('De naam van de bezoeker wordt er automatisch achter geplakt, bv. "Hoi Anna,".')->required()->maxLength(255),
                Textarea::make('email_intro')->label('Introductiezin')->rows(2)->required(),
                Textarea::make('email_outro')->label('Slotzin voor de PDF-verwijzing')->rows(2)->required(),
                TextInput::make('email_cta_label')->label('Knoptekst')->required()->maxLength(255),
                TextInput::make('email_cta_url')->label('Knoplink')->url()->required()->maxLength(255),
            ])
            ->action(function (array $data): void {
                $this->getSiteContent()->update($data);

                Notification::make()->title('E-mailtekst bijgewerkt')->success()->send();
            });
    }
}
