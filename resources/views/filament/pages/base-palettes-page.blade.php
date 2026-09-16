<x-filament-panels::page>
    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Elke woonstijl heeft zijn eigen basispaletten — de bezoeker kiest er ná de
            stijlberekening één van, vóór de accentkleurenstap. Een accentkleur die exact dezelfde
            kleur heeft als een kleur uit het gekozen basispalet wordt in de klant-quiz automatisch
            als "Zit al in je basis" getoond en is dan niet apart te kiezen.
        </p>
    </x-filament::section>

    @php $palettesByStyle = $this->getPalettesByStyle(); @endphp

    <div class="space-y-10">
        @foreach (\App\Support\QuizStructure::styleOptions() as $styleKey => $styleLabel)
            @php $palettes = $palettesByStyle[$styleKey] ?? collect(); @endphp

            <div>
                <div @class(['mb-4 flex items-center gap-3', 'border-t border-gray-200 pt-8 dark:border-white/10' => ! $loop->first])>
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">{{ $styleLabel }}</h2>
                    <span class="text-sm text-gray-400 dark:text-gray-500">
                        · {{ $palettes->count() }} {{ $palettes->count() === 1 ? 'palet' : 'paletten' }}
                    </span>
                    <x-filament::button
                        size="sm"
                        color="gray"
                        icon="heroicon-o-plus"
                        wire:click="mountAction('createPalette', {{ \Illuminate\Support\Js::from(['styleKey' => $styleKey]) }})"
                    >
                        Palet toevoegen
                    </x-filament::button>
                </div>

                <div
                    class="space-y-3"
                    x-sortable
                    x-on:end.stop="$wire.reorderPalettes($event.target.sortable.toArray())"
                >
                    @forelse ($palettes as $palette)
                        <x-filament::section x-sortable-item="{{ $palette->id }}">
                            <div class="flex items-start gap-4">
                                <x-filament::icon-button
                                    icon="heroicon-m-bars-2"
                                    label="Sleep om te herordenen"
                                    x-sortable-handle
                                    class="mt-1 cursor-move"
                                />

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $palette->name }}</span>
                                        <x-filament::badge
                                            tag="button"
                                            :color="$palette->is_active ? 'success' : 'gray'"
                                            wire:click="toggleActive({{ $palette->id }})"
                                            class="cursor-pointer"
                                        >
                                            {{ $palette->is_active ? 'Actief' : 'Inactief' }}
                                        </x-filament::badge>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $palette->description }}</p>

                                    <div class="mt-3 flex flex-wrap gap-3">
                                        @foreach ($palette->colors as $color)
                                            <div class="flex items-center gap-2">
                                                <span
                                                    style="background: {{ $color['hex'] ?? '#e7ddd1' }};"
                                                    class="h-6 w-6 shrink-0 rounded-md border border-gray-300 dark:border-white/20"
                                                ></span>
                                                <span class="text-xs text-gray-600 dark:text-gray-300">{{ $color['name'] ?? '' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-1">
                                    <x-filament::icon-button
                                        icon="heroicon-o-pencil-square"
                                        label="Bewerken"
                                        wire:click="mountAction('editPalette', {{ \Illuminate\Support\Js::from(['paletteId' => $palette->id]) }})"
                                    />
                                    <x-filament::icon-button
                                        icon="heroicon-o-trash"
                                        color="danger"
                                        label="Verwijderen"
                                        wire:click="mountAction('deletePalette', {{ \Illuminate\Support\Js::from(['paletteId' => $palette->id]) }})"
                                    />
                                </div>
                            </div>
                        </x-filament::section>
                    @empty
                        <x-filament::section>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Nog geen basispaletten voor deze stijl.</p>
                        </x-filament::section>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
