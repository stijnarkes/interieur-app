<x-filament-panels::page>
    @php $settings = $this->getSettings(); @endphp

    <x-filament::section
        heading="Drempelwaarden"
        description="Bepalen wanneer de uitslag een duidelijke winnaar toont, of juist een gemengd profiel van meerdere stijlen."
        :header-actions="[$this->editThresholdsAction()]"
    >
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Duidelijke winnaar vanaf</dt>
                <dd class="text-lg font-bold text-gray-950 dark:text-white">{{ $settings->primary_dominant_margin }} procentpunt</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Twee bijna-gelijke stijlen onder</dt>
                <dd class="text-lg font-bold text-gray-950 dark:text-white">{{ $settings->close_pair_margin }} procentpunt</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Drie bijna-gelijke stijlen onder</dt>
                <dd class="text-lg font-bold text-gray-950 dark:text-white">{{ $settings->close_triple_margin }} procentpunt</dd>
            </div>
        </dl>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
