@php
    $result = $getState() ?? [];
    $primaryLabel = $result['primaryStyle']['label'] ?? null;
    $secondaryLabel = $result['secondaryStyleLabel'] ?? null;
    $description = $result['description'] ?? null;
@endphp

<div>
    @if ($primaryLabel)
        <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $primaryLabel }}</p>

        @if ($secondaryLabel)
            <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">met {{ $secondaryLabel }}-invloeden</p>
        @endif

        @if ($description)
            <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">{{ $description }}</p>
        @endif
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Nog geen uitslag beschikbaar.</p>
    @endif
</div>
