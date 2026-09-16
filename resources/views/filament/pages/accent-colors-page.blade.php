<x-filament-panels::page>
    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Deze kleuren worden aangeboden bij de laatste quizvraag ("Welke accentkleuren spreken jou het meeste aan?"),
            ná de stijlberekening. Per bezoeker worden alleen kleuren getoond die aan diens berekende
            woonstijl(en) gekoppeld zijn — koppel een kleur aan meerdere stijlen als die overal past.
        </p>
    </x-filament::section>

    @php $colors = $this->getColors(); @endphp

    <div
        class="space-y-3"
        x-sortable
        x-on:end.stop="$wire.reorderColors($event.target.sortable.toArray())"
    >
        @forelse ($colors as $color)
            <x-filament::section x-sortable-item="{{ $color->id }}">
                <div class="flex items-center gap-4">
                    <x-filament::icon-button
                        icon="heroicon-m-bars-2"
                        label="Sleep om te herordenen"
                        x-sortable-handle
                        class="cursor-move"
                    />

                    <span
                        style="background: {{ $color->hex }};"
                        class="h-10 w-10 shrink-0 rounded-full border border-gray-300 dark:border-white/20"
                    ></span>

                    <span class="w-40 shrink-0 truncate text-sm font-medium text-gray-950 dark:text-white">
                        {{ $color->name }}
                    </span>

                    <div class="flex flex-1 flex-wrap gap-1">
                        @forelse ($color->linkedStyleKeys() as $styleKey)
                            <x-filament::badge color="primary">
                                {{ \App\Support\QuizStructure::styleLabel($styleKey) }}
                            </x-filament::badge>
                        @empty
                            <x-filament::badge color="danger">Geen stijl gekoppeld</x-filament::badge>
                        @endforelse
                    </div>

                    <x-filament::badge
                        tag="button"
                        :color="$color->is_active ? 'success' : 'gray'"
                        wire:click="toggleActive({{ $color->id }})"
                        class="shrink-0 cursor-pointer"
                    >
                        {{ $color->is_active ? 'Actief' : 'Inactief' }}
                    </x-filament::badge>

                    <div class="flex shrink-0 items-center gap-1">
                        <x-filament::icon-button
                            icon="heroicon-o-pencil-square"
                            label="Bewerken"
                            wire:click="mountAction('editColor', {{ \Illuminate\Support\Js::from(['colorId' => $color->id]) }})"
                        />
                        <x-filament::icon-button
                            icon="heroicon-o-trash"
                            color="danger"
                            label="Verwijderen"
                            wire:click="mountAction('deleteColor', {{ \Illuminate\Support\Js::from(['colorId' => $color->id]) }})"
                        />
                    </div>
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nog geen accentkleuren toegevoegd.</p>
            </x-filament::section>
        @endforelse
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
