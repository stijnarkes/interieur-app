<x-filament-panels::page>
    @php
        $sections = $this->getSections();
        $materialsByStyle = $this->getMaterialsByStyle();
        $uploaded = $this->getUploadedCount();
        $total = $this->getTotalCount();
    @endphp

    <x-filament::section>
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-semibold text-gray-900 dark:text-white">{{ $uploaded }} / {{ $total }}</span>
                afbeeldingen geüpload. Ontbrekende foto's tonen in de app een placeholder totdat je ze hier uploadt.
            </p>
        </div>
    </x-filament::section>

    <div class="mt-10">
        @include('filament.pages.partials.image-slot-sections', ['sections' => $sections])
    </div>

    <div class="mt-10 border-t border-gray-200 pt-8 dark:border-white/10">
        <h2 class="mb-4 text-lg font-bold text-gray-950 dark:text-white">Materialen per woonstijl</h2>

        <div class="space-y-4">
            @foreach ($materialsByStyle as $styleGroup)
                @php $materials = $styleGroup['materials']; @endphp

                <x-filament::section
                    :heading="$styleGroup['label']"
                    :description="$materials->count().' '.($materials->count() === 1 ? 'materiaal' : 'materialen')"
                    icon="heroicon-o-swatch"
                    collapsible
                    collapsed
                    :header-actions="[
                        $this->createMaterialAction()->arguments(['styleKey' => $styleGroup['key']]),
                    ]"
                >
                    @if ($materials->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nog geen materialen voor deze stijl.</p>
                    @else
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                            @foreach ($materials as $material)
                                @php $url = $material->thumbnailUrl(); @endphp

                                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                                    <div class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-gray-100 dark:bg-gray-800">
                                        @if ($url)
                                            <img
                                                src="{{ $url }}"
                                                alt="{{ $material->name }}"
                                                class="h-full w-full object-cover"
                                            />
                                        @else
                                            <x-heroicon-o-photo class="h-8 w-8 text-gray-400" />
                                        @endif
                                    </div>

                                    <div class="p-2.5">
                                        <p class="truncate text-xs font-semibold text-gray-900 dark:text-white" title="{{ $material->name }}">
                                            {{ $material->name }}
                                        </p>
                                        <code class="mt-1 block truncate text-[10px] text-gray-400">{{ $material->filename }}</code>

                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            <x-filament::button
                                                size="xs"
                                                color="gray"
                                                wire:click="mountAction('uploadMaterial', {{ \Illuminate\Support\Js::from(['materialId' => $material->id]) }})"
                                            >
                                                {{ $url ? 'Vervangen' : 'Uploaden' }}
                                            </x-filament::button>

                                            @if ($url)
                                                <x-filament::icon-button
                                                    icon="heroicon-o-trash"
                                                    color="warning"
                                                    label="Verwijder afbeelding"
                                                    wire:click="mountAction('deleteMaterialImage', {{ \Illuminate\Support\Js::from(['materialId' => $material->id]) }})"
                                                />
                                            @endif

                                            <x-filament::icon-button
                                                icon="heroicon-o-x-circle"
                                                color="danger"
                                                label="Materiaal verwijderen"
                                                wire:click="mountAction('deleteMaterial', {{ \Illuminate\Support\Js::from(['materialId' => $material->id]) }})"
                                            />
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-filament::section>
            @endforeach
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
