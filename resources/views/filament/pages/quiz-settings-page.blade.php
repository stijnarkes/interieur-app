<x-filament-panels::page>
    @php $settings = $this->getSettings(); @endphp

    <x-filament::section
        heading="Drempel voor de tweede invloed"
        description="Bepaalt wanneer de op-één-na-hoogste stijl als 'invloed' naast de basisstijl getoond wordt."
        :header-actions="[$this->editThresholdAction()]"
    >
        <dl>
            <dt class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Minimaal percentage van de basisscore</dt>
            <dd class="text-lg font-bold text-gray-950 dark:text-white">{{ $settings->secondary_influence_ratio }}%</dd>
        </dl>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
