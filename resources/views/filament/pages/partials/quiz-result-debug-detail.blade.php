<div class="space-y-4">
    <div>
        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Uitslag</h3>
        <p class="text-sm text-gray-800 dark:text-gray-200">
            Basisstijl: <strong>{{ $explanation['primary_style'] ?? '—' }}</strong>
            @if ($explanation['secondary_style'])
                — Invloed: <strong>{{ $explanation['secondary_style'] }}</strong>
            @endif
        </p>
    </div>

    <div>
        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Punten per stijl (drempel voor invloed: {{ $explanation['secondary_influence_ratio'] }}% van de basisscore, + minstens 2 verschillende vragen)
        </h3>
        <table class="mt-1 w-full text-xs text-gray-800 dark:text-gray-200">
            <thead>
                <tr class="border-b border-gray-200 dark:border-white/10">
                    <th class="py-1 text-start font-medium">Stijl</th>
                    <th class="py-1 text-start font-medium">Punten</th>
                    <th class="py-1 text-start font-medium">Aantal vragen</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($explanation['style_scores'] as $styleKey => $score)
                    @continue($score <= 0)
                    <tr class="border-b border-gray-100 dark:border-white/5">
                        <td class="py-1">
                            {{ $styleKey }}
                            @if ($styleKey === $explanation['primary_style']) <span class="text-success-600 dark:text-success-400">(basis)</span> @endif
                            @if ($styleKey === $explanation['secondary_style']) <span class="text-primary-600 dark:text-primary-400">(invloed)</span> @endif
                        </td>
                        <td class="py-1">{{ rtrim(rtrim(number_format($score, 2, ',', ''), '0'), ',') }}</td>
                        <td class="py-1">{{ $explanation['style_question_counts'][$styleKey] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>
        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ruwe antwoorden</h3>
        <pre class="mt-1 max-h-40 overflow-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ json_encode($result->answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</div>
