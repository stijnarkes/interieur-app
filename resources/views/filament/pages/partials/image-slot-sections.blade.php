{{-- Verwacht een `$sections`-variabele (array van {heading, folder, slots}) en draait binnen een
     component dat de ManagesImageSlots-trait gebruikt (imageUrl(), mountAction('upload'/'delete', ...)). --}}
<div class="space-y-4">
    @foreach ($sections as $section)
        @php $sectionUploaded = collect($section['slots'])->filter(fn ($slot) => $this->imageUrl($section['folder'], $slot['filename']))->count(); @endphp

        <div class="mb-4 flex items-center gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-600 text-white">
                <x-heroicon-o-home class="h-4 w-4" />
            </span>
            <div>
                <h2 class="text-lg font-bold text-gray-950 dark:text-white">{{ $section['heading'] }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    public/images/interior/{{ $section['folder'] }}/ · {{ $sectionUploaded }} / {{ count($section['slots']) }} geüpload
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ($section['slots'] as $slot)
                @php
                    $url = $this->imageUrl($section['folder'], $slot['filename']);
                    $arguments = [
                        'folder' => $section['folder'],
                        'filename' => $slot['filename'],
                        'label' => $slot['style'].' — '.$slot['label'],
                    ];
                @endphp

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <div class="relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-gray-100 dark:bg-gray-800">
                        @if ($url)
                            <img
                                src="{{ $url }}"
                                alt="{{ $slot['style'] }} — {{ $slot['label'] }}"
                                class="h-full w-full object-cover"
                            />
                        @else
                            <x-heroicon-o-photo class="h-8 w-8 text-gray-400" />
                        @endif

                        <x-filament::badge
                            :color="$url ? 'success' : 'warning'"
                            class="absolute shadow-sm"
                            style="top: 6px; right: 6px;"
                        >
                            {{ $url ? 'Geüpload' : 'Ontbreekt' }}
                        </x-filament::badge>
                    </div>

                    <div class="p-3">
                        <p class="truncate text-sm font-semibold text-gray-950 dark:text-white" title="{{ $slot['style'] }}">
                            {{ $slot['style'] }}
                        </p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $slot['label'] }}">
                            {{ $slot['label'] }}
                        </p>
                        <code class="mt-1 block truncate text-[10px] text-gray-400">{{ $slot['filename'] }}</code>

                        <div class="mt-2.5 flex gap-1.5">
                            <x-filament::button
                                size="xs"
                                color="gray"
                                wire:click="mountAction('upload', {{ \Illuminate\Support\Js::from($arguments) }})"
                            >
                                {{ $url ? 'Vervangen' : 'Uploaden' }}
                            </x-filament::button>

                            @if ($url)
                                <x-filament::icon-button
                                    icon="heroicon-o-trash"
                                    color="danger"
                                    label="Verwijderen"
                                    wire:click="mountAction('delete', {{ \Illuminate\Support\Js::from($arguments) }})"
                                />
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
