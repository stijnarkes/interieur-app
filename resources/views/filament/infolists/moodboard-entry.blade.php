@php
    $items = $getState() ?? [];

    $resolveImage = function (?string $path) {
        if (! $path) {
            return null;
        }
        $relative = ltrim($path, '/');
        $absolute = realpath(public_path($relative));
        $imagesRoot = realpath(public_path('images'));

        if (! $absolute || ! $imagesRoot || ! str_starts_with($absolute, $imagesRoot.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return asset($relative);
    };
@endphp

@if (empty($items))
    <p class="text-sm text-gray-500 dark:text-gray-400">Geen moodboard beschikbaar.</p>
@else
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 8px;">
        @foreach ($items as $item)
            @php $url = $resolveImage($item['image'] ?? null); @endphp
            <div>
                <div
                    class="flex items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800"
                    style="aspect-ratio: 4 / 3;"
                >
                    @if ($url)
                        <img
                            src="{{ $url }}"
                            alt="{{ $item['title'] ?? '' }}"
                            class="h-full w-full object-cover"
                        />
                    @else
                        <x-heroicon-o-photo class="h-5 w-5 text-gray-400" />
                    @endif
                </div>
                <span class="mt-1 block truncate text-[10px] text-gray-500 dark:text-gray-400" title="{{ $item['title'] ?? '' }}">
                    {{ $item['title'] ?? '' }}
                </span>
            </div>
        @endforeach
    </div>
@endif
