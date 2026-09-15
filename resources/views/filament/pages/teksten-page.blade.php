<x-filament-panels::page>
    @php $content = $this->getSiteContent(); @endphp

    <x-filament::section
        heading="Startscherm"
        description="Titel, introductietekst en knoptekst op de allereerste pagina van de stijltest."
        :header-actions="[$this->editStartScreenAction()]"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $content->start_title }} — {{ $content->start_button_label }}</p>
    </x-filament::section>

    <x-filament::section
        heading="Overgangsschermen"
        description="De twee tussenschermen tussen de onderdelen van de stijltest."
    >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($this->getTransitionSections() as $section)
                <x-filament::section>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $section->title }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $section->tagline }}</p>
                        </div>
                        <x-filament::icon-button
                            icon="heroicon-o-pencil-square"
                            label="Bewerken"
                            wire:click="mountAction('editSection', {{ \Illuminate\Support\Js::from(['sectionId' => $section->section_id]) }})"
                        />
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section
        heading="Resultatenpagina"
        description="Heldenblok, rapport-teaser, aanvraagformulier en bevestiging na verzenden."
        :header-actions="[$this->editResultCopyAction()]"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $content->hero_eyebrow }} — {{ $content->lead_heading }}</p>
    </x-filament::section>

    <x-filament::section
        heading="E-mail"
        description="De bevestigingsmail met het woonstijlrapport als bijlage."
        :header-actions="[$this->editEmailCopyAction()]"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $content->email_subject }}</p>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
