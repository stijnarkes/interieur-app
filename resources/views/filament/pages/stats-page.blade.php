<x-filament-panels::page>
    <div class="space-y-8">

        {{-- Top stijlen --}}
        <x-filament::section>
            <x-slot name="heading">Populairste stijlen</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-4 font-semibold text-gray-600 dark:text-gray-400">Stijl</th>
                            <th class="py-2 pr-4 font-semibold text-gray-600 dark:text-gray-400">Aantal</th>
                            <th class="py-2 font-semibold text-gray-600 dark:text-gray-400">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->getTopStyles() as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pr-4 font-medium">{{ $row['style'] }}</td>
                                <td class="py-2 pr-4">{{ $row['count'] }}</td>
                                <td class="py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 rounded-full bg-primary-500" style="width: {{ $row['percentage'] }}%; min-width: 4px; max-width: 200px;"></div>
                                        <span>{{ $row['percentage'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Top sfeerwoorden --}}
        <x-filament::section>
            <x-slot name="heading">Populairste sfeerwoorden</x-slot>

            <div class="flex flex-wrap gap-2">
                @foreach ($this->getTopMoodWords() as $item)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                        {{ $item['word'] }}
                        <span class="rounded-full bg-primary-200 px-1.5 py-0.5 text-xs font-semibold text-primary-800 dark:bg-primary-800 dark:text-primary-200">{{ $item['count'] }}</span>
                    </span>
                @endforeach
                @if ($this->getTopMoodWords()->isEmpty())
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Nog geen data beschikbaar.</p>
                @endif
            </div>
        </x-filament::section>

        {{-- Top kleuren --}}
        <x-filament::section>
            <x-slot name="heading">Populairste kleuren</x-slot>

            <div class="flex flex-wrap gap-2">
                @foreach ($this->getTopColors() as $item)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-warning-50 px-3 py-1 text-sm font-medium text-warning-700 dark:bg-warning-950 dark:text-warning-300">
                        {{ $item['word'] }}
                        <span class="rounded-full bg-warning-200 px-1.5 py-0.5 text-xs font-semibold text-warning-800 dark:bg-warning-800 dark:text-warning-200">{{ $item['count'] }}</span>
                    </span>
                @endforeach
                @if ($this->getTopColors()->isEmpty())
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Nog geen data beschikbaar.</p>
                @endif
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
