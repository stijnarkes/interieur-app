<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Deze inhoud is de enige bron waar de AI-adviestekst uit mag putten — pas hier aan wat er
        over elke woonstijl gezegd mag worden.
    </p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->getProfiles() as $profile)
            <x-filament::section>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">{{ $profile->label }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $profile->subtitle }}</p>
                    </div>
                    <x-filament::icon-button
                        icon="heroicon-o-pencil-square"
                        label="Bewerken"
                        wire:click="mountAction('editProfile', {{ \Illuminate\Support\Js::from(['profileId' => $profile->id]) }})"
                    />
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
