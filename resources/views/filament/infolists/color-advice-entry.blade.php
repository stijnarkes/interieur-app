@php
    $result = $getState() ?? [];
    $basePaletteName = $result['basePaletteName'] ?? null;
    $palette = $result['personalPalette'] ?? [];
    $accentColors = $result['accentColors'] ?? [];
    $explanation = $result['colorExplanation'] ?? null;
    $furnitureAdvice = $result['primaryStyle']['furnitureAdvice'] ?? null;
    $recipe = $result['primaryStyle']['recipe'] ?? [];
@endphp

<div class="space-y-5">
    <div>
        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Basispalet @if ($basePaletteName) — {{ $basePaletteName }} @endif
        </p>
        @if (empty($palette))
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">Geen basispalet gekozen.</p>
        @else
            <div class="mt-2 flex flex-wrap gap-3">
                @foreach ($palette as $color)
                    <div style="width: 88px;">
                        <div class="rounded-lg border border-gray-300 dark:border-gray-600" style="width: 88px; height: 52px; background: {{ $color['hex'] ?? '#e5e5e5' }};"></div>
                        <p class="mt-1 truncate text-center text-xs font-medium text-gray-900 dark:text-white" title="{{ $color['name'] ?? '' }}">{{ $color['name'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Accentkleur(en)</p>
        @if (empty($accentColors))
            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">Geen accentkleur gekozen.</p>
        @else
            <div class="mt-2 flex flex-wrap gap-3">
                @foreach ($accentColors as $color)
                    <div style="width: 88px;">
                        <div class="rounded-lg border border-gray-300 dark:border-gray-600" style="width: 88px; height: 52px; background: {{ $color['hex'] ?? '#e5e5e5' }};"></div>
                        <p class="mt-1 truncate text-center text-xs font-medium text-gray-900 dark:text-white" title="{{ $color['name'] ?? '' }}">{{ $color['name'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($explanation)
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $explanation }}</p>
    @endif

    @if ($furnitureAdvice || ! empty($recipe))
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Interieuradvies</p>

            @if ($furnitureAdvice)
                <p class="mt-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $furnitureAdvice }}</p>
            @endif

            @if (! empty($recipe))
                <dl class="mt-2 space-y-1">
                    @foreach ($recipe as $item)
                        <div class="flex gap-2 text-sm">
                            <dt class="font-medium text-gray-900 dark:text-white">{{ $item['label'] ?? '' }}:</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $item['value'] ?? '' }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    @endif
</div>
