<x-filament-panels::page>
    @php $settings = $this->getSettings(); @endphp

    <x-filament::section
        heading="Drempel voor de tweede invloed"
        description="Bepaalt wanneer de op-één-na-hoogste stijl als 'invloed' naast de basisstijl getoond wordt (naast de andere twee, niet-instelbare eisen: een positieve uitslagScore en op minstens 2 vragen boven het vraaggemiddelde van die stijl)."
        :header-actions="[$this->editThresholdAction()]"
    >
        <dl>
            <dt class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Maximaal verschil in uitslagScore met de hoofdstijl</dt>
            <dd class="text-lg font-bold text-gray-950 dark:text-white">{{ $settings->secondary_influence_max_gap }}</dd>
        </dl>
    </x-filament::section>

    <x-filament::section
        heading="Partnerfunctie"
        description="'Ontdek jullie gezamenlijke woonstijl' — zolang uitgeschakeld is deze functie nergens zichtbaar of bereikbaar, ook niet via een directe link."
        :header-actions="[$this->togglePartnerFeatureAction()]"
    >
        <dl>
            <dt class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="text-lg font-bold text-gray-950 dark:text-white">{{ $settings->partner_feature_enabled ? 'Ingeschakeld' : 'Uitgeschakeld' }}</dd>
        </dl>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
