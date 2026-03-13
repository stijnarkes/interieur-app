<x-filament-panels::page>
    @php
        $submissions = $this->getSubmissionsWithPhotos();
    @endphp

    @if ($submissions->isEmpty())
        <x-filament::section>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Er zijn nog geen inzendingen met een kamerafoto.</p>
        </x-filament::section>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($submissions as $submission)
                @php
                    $photoUrl  = $this->getPhotoUrl($submission);
                    $detailUrl = $this->getDetailUrl($submission);
                @endphp

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    @if ($photoUrl)
                        <a href="{{ $detailUrl }}">
                            <img
                                src="{{ $photoUrl }}"
                                alt="Kamerafoto van {{ $submission->name ?? 'onbekend' }}"
                                class="h-48 w-full object-cover transition-opacity hover:opacity-90"
                            />
                        </a>
                    @else
                        <div class="flex h-48 items-center justify-center bg-gray-100 dark:bg-gray-800">
                            <x-heroicon-o-photo class="h-12 w-12 text-gray-400" />
                        </div>
                    @endif

                    <div class="p-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $submission->created_at->format('d-m-Y H:i') }}
                        </div>
                        <div class="mt-0.5 font-medium text-sm text-gray-900 dark:text-white">
                            {{ $submission->name ?? '—' }}
                        </div>
                        <div class="mt-1">
                            <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                                {{ $submission->style }}
                            </span>
                        </div>
                        <div class="mt-2">
                            <a
                                href="{{ $detailUrl }}"
                                class="text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                Bekijk detail &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
