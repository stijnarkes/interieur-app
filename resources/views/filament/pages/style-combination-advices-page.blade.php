<x-filament-panels::page>
    <x-filament::section>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Redactionele combinatietips voor de partnerfunctie ("Ontdek jullie gezamenlijke
            woonstijl") — vaste set van 21 paren. Alleen <strong>gepubliceerde</strong> combinaties
            worden aan bezoekers getoond; een concept-combinatie valt automatisch terug op een
            neutrale tekst totdat je 'm publiceert.
        </p>
    </x-filament::section>

    @php $advicesByStyle = $this->getAdvicesByStyle(); @endphp

    <div class="space-y-10">
        @foreach (\App\Support\QuizStructure::styleOptions() as $styleKey => $styleLabel)
            @php $advices = $advicesByStyle[$styleKey] ?? collect(); @endphp

            <div>
                <div @class(['mb-4 flex items-center gap-3', 'border-t border-gray-200 pt-8 dark:border-white/10' => ! $loop->first])>
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">{{ $styleLabel }}</h2>
                </div>

                <div class="space-y-3">
                    @foreach ($advices as $advice)
                        <x-filament::section>
                            <div class="flex items-start gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $advice->title }}</span>
                                        <x-filament::badge
                                            tag="button"
                                            :color="$advice->status === 'published' ? 'success' : 'gray'"
                                            wire:click="togglePublished({{ $advice->id }})"
                                            class="cursor-pointer"
                                        >
                                            {{ $advice->status === 'published' ? 'Gepubliceerd' : 'Concept' }}
                                        </x-filament::badge>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $advice->intro }}</p>
                                </div>

                                <div class="flex shrink-0 items-center gap-1">
                                    <x-filament::icon-button
                                        icon="heroicon-o-pencil-square"
                                        label="Bewerken"
                                        wire:click="mountAction('editAdvice', {{ \Illuminate\Support\Js::from(['adviceId' => $advice->id]) }})"
                                    />
                                </div>
                            </div>
                        </x-filament::section>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
