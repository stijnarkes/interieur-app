<x-filament-panels::page>
    <div class="fi-ta overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
        <table class="fi-ta-table w-full text-start">
            <thead>
                <tr class="border-b border-gray-200 dark:border-white/10">
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tijdstip</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Basisstijl</th>
                    <th class="px-4 py-3 text-start text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Invloed</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->getResults() as $result)
                    <tr class="border-b border-gray-100 dark:border-white/5">
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $result->created_at?->format('d-m-Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $result->primary_style ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $result->secondary_style ?? '—' }}</td>
                        <td class="px-4 py-3 text-end">
                            <x-filament::button size="sm" wire:click="mountAction('viewResult', {{ \Illuminate\Support\Js::from(['resultId' => $result->id]) }})">
                                Bekijken
                            </x-filament::button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">Nog geen quizresultaten.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
