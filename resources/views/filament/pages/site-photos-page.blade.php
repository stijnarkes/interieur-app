<x-filament-panels::page>
    @php
        $sections = $this->getSections();
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

    <x-filament-actions::modals />
</x-filament-panels::page>
