@php
    $palette = $getState() ?? [];
@endphp

@if (empty($palette))
    <p class="text-sm text-gray-500 dark:text-gray-400">Geen kleurenpalet beschikbaar.</p>
@else
    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
        @foreach ($palette as $color)
            <div style="width: 96px;">
                <div
                    class="rounded-lg border border-gray-300 dark:border-gray-600"
                    style="width: 96px; height: 56px; background: {{ $color['hex'] ?? '#e5e5e5' }};"
                ></div>
                <p class="mt-1.5 truncate text-center text-xs font-semibold text-gray-900 dark:text-white" title="{{ $color['name'] ?? '' }}">
                    {{ $color['name'] ?? '' }}
                </p>
            </div>
        @endforeach
    </div>
@endif
