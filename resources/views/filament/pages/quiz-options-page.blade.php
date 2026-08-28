<x-filament-panels::page>
    @php $questionsBySection = collect($this->getQuestions())->groupBy('section'); @endphp

    <div class="space-y-10">
        @foreach (\App\Support\QuizStructure::SECTIONS as $sectionKey => $sectionMeta)
            @php $sectionQuestions = $questionsBySection->get($sectionKey, collect()); @endphp

            <div>
                <div @class(['mb-4 flex items-center gap-3', 'border-t border-gray-200 pt-8 dark:border-white/10' => ! $loop->first])>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white">
                        {{ $loop->iteration }}
                    </span>
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">
                        {{ $sectionMeta['title'] }}
                    </h2>
                    <span class="text-sm text-gray-400 dark:text-gray-500">
                        · {{ $sectionQuestions->count() }} {{ $sectionQuestions->count() === 1 ? 'vraag' : 'vragen' }}
                    </span>
                </div>

                <div
                    class="space-y-4"
                    x-sortable
                    x-on:end.stop="$wire.reorderQuestions($event.target.sortable.toArray())"
                >
                    @foreach ($sectionQuestions as $question)
                        @php
                            $options = $question->options;
                            $activeCount = $options->where('is_active', true)->count();
                        @endphp

                        <x-filament::section
                            x-sortable-item="{{ $question->id }}"
                            :heading="$question->title"
                            :description="$options->count().' opties · '.$activeCount.' actief'"
                            icon="heroicon-o-question-mark-circle"
                            collapsible
                            collapsed
                            :header-actions="[
                                $this->createOptionAction()->arguments(['questionId' => $question->question_key]),
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
                                        label="Vraag bewerken"
                                        wire:click="mountAction('editQuestion', {{ \Illuminate\Support\Js::from(['questionId' => $question->id]) }})"
                                    />
                                    <x-filament::icon-button
                                        icon="heroicon-o-trash"
                                        color="danger"
                                        label="Vraag verwijderen"
                                        wire:click="mountAction('deleteQuestion', {{ \Illuminate\Support\Js::from(['questionId' => $question->id]) }})"
                                    />
                                </div>
                            </x-slot>

                            @if ($options->isEmpty())
                                <p class="text-sm text-gray-500 dark:text-gray-400">Nog geen antwoordopties voor deze vraag.</p>
                            @else
                                <div>
                                    @foreach ($options as $option)
                                        <div @class(['flex items-center gap-4 py-3', 'border-t border-gray-200 dark:border-white/10' => ! $loop->first])>
                                            <img
                                                src="{{ $option->thumbnailUrl() }}"
                                                alt="{{ $option->title }}"
                                                style="width: 56px; height: 56px; object-fit: cover; flex-shrink: 0;"
                                                class="rounded-lg bg-gray-100 dark:bg-gray-800"
                                                onerror="this.style.visibility='hidden'"
                                            />

                                            <x-filament::badge color="primary" class="shrink-0">
                                                {{ \App\Support\QuizStructure::styleLabel($option->primary_style) }}
                                            </x-filament::badge>

                                            <span class="flex-1 truncate text-sm font-medium text-gray-950 dark:text-white">
                                                {{ $option->title }}
                                            </span>

                                            @if ($option->showroom_product)
                                                <x-heroicon-o-shopping-bag class="h-4 w-4 shrink-0 text-gray-400" title="Showroomproduct" />
                                            @endif

                                            <x-filament::badge
                                                tag="button"
                                                :color="$option->is_active ? 'success' : 'gray'"
                                                wire:click="toggleActive({{ $option->id }})"
                                                class="shrink-0 cursor-pointer"
                                            >
                                                {{ $option->is_active ? 'Actief' : 'Inactief' }}
                                            </x-filament::badge>

                                            <div class="flex shrink-0 items-center gap-1">
                                                <x-filament::icon-button
                                                    icon="heroicon-o-pencil-square"
                                                    label="Bewerken"
                                                    wire:click="mountAction('editOption', {{ \Illuminate\Support\Js::from(['optionId' => $option->id]) }})"
                                                />

                                                @if ($option->hasImage())
                                                    <x-filament::icon-button
                                                        icon="heroicon-o-trash"
                                                        color="warning"
                                                        label="Verwijder afbeelding"
                                                        wire:click="mountAction('deleteImage', {{ \Illuminate\Support\Js::from(['optionId' => $option->id]) }})"
                                                    />
                                                @endif

                                                @if ($option->image_path)
                                                    <x-filament::icon-button
                                                        icon="heroicon-o-x-circle"
                                                        color="danger"
                                                        label="Verwijderen"
                                                        wire:click="mountAction('deleteOption', {{ \Illuminate\Support\Js::from(['optionId' => $option->id]) }})"
                                                    />
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </x-filament::section>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
