<x-filament-panels::page>
    <div
        class="space-y-4"
        x-sortable
        x-on:end.stop="$wire.reorderPalettes($event.target.sortable.toArray())"
    >
        @foreach ($this->getPalettes() as $palette)
            @php $colors = $palette->colors; @endphp

            <x-filament::section
                x-sortable-item="{{ $palette->id }}"
                :heading="$palette->name"
                :description="$colors->count().' '.($colors->count() === 1 ? 'kleur' : 'kleuren')"
                icon="heroicon-o-swatch"
                collapsible
                collapsed
                :header-actions="[
                    $this->createColorAction()->arguments(['paletteId' => $palette->id]),
                ]"
            >
                <x-slot name="headerEnd">
                    <div class="flex items-center gap-1" x-on:click.stop="">
                        <x-filament::icon-button
                            icon="heroicon-m-bars-2"
                            label="Sleep om te herordenen"
                            x-sortable-handle
                            class="cursor-move"
                        />
                        <x-filament::icon-button
                            icon="heroicon-o-pencil-square"
                            label="Palet bewerken"
                            wire:click="mountAction('editPalette', {{ \Illuminate\Support\Js::from(['paletteId' => $palette->id]) }})"
                        />
                        <x-filament::icon-button
                            icon="heroicon-o-trash"
                            color="danger"
                            label="Palet verwijderen"
                            wire:click="mountAction('deletePalette', {{ \Illuminate\Support\Js::from(['paletteId' => $palette->id]) }})"
                        />
                    </div>
                </x-slot>

                @if ($colors->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nog geen kleuren in dit palet.</p>
                @else
                    <div
                        x-sortable
                        x-on:end.stop="$wire.reorderColors($event.target.sortable.toArray())"
                    >
                        @foreach ($colors as $color)
                            <div
                                x-sortable-item="{{ $color->id }}"
                                @class(['flex items-center gap-3 py-2.5', 'border-t border-gray-200 dark:border-white/10' => ! $loop->first])
                            >
                                <span x-sortable-handle class="cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <x-heroicon-m-bars-2 class="h-4 w-4" />
                                </span>

                                <span
                                    class="h-8 w-8 shrink-0 rounded-full border border-gray-200 dark:border-white/10"
                                    style="background-color: {{ $color->hex }};"
                                ></span>

                                <span class="flex-1 truncate text-sm font-medium text-gray-950 dark:text-white">
                                    {{ $color->name }}
                                </span>

                                <code class="shrink-0 text-xs text-gray-400">{{ $color->hex }}</code>

                                <div class="flex shrink-0 items-center gap-1">
                                    <x-filament::icon-button
                                        icon="heroicon-o-pencil-square"
                                        label="Kleur bewerken"
                                        wire:click="mountAction('editColor', {{ \Illuminate\Support\Js::from(['colorId' => $color->id]) }})"
                                    />
                                    <x-filament::icon-button
                                        icon="heroicon-o-x-circle"
                                        color="danger"
                                        label="Kleur verwijderen"
                                        wire:click="mountAction('deleteColor', {{ \Illuminate\Support\Js::from(['colorId' => $color->id]) }})"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        @endforeach
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
