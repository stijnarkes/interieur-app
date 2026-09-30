@php
    $record = $getRecord();
    $scoring = app(\App\Services\QuizScoringService::class)->explain($record->quiz_answers ?? []);
    $ranked = collect($scoring['uitslag_scores'] ?? [])->sortDesc();
    $styleKeys = \App\Support\QuizStructure::styleKeys();
@endphp

<div class="space-y-5">
    <div>
        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Uitslagscores per stijl (kunnen negatief zijn — geen percentages)
        </p>
        <table class="mt-2 w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="pb-2">#</th>
                    <th class="pb-2">Stijl</th>
                    <th class="pb-2 text-right">uitslagScore</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ranked as $styleKey => $score)
                    <tr class="border-t border-gray-100 dark:border-white/5">
                        <td class="py-1.5 text-gray-400">{{ $loop->iteration }}</td>
                        <td class="py-1.5 font-medium text-gray-950 dark:text-white">{{ \App\Support\QuizStructure::styleLabel($styleKey) }}</td>
                        <td @class([
                            'py-1.5 text-right font-mono',
                            'text-danger-600' => $score < 0,
                            'text-gray-700 dark:text-gray-300' => $score >= 0,
                        ])>{{ number_format($score, 3, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
            Drempel voor een tweede stijl ("invloed"): max. verschil {{ number_format($scoring['secondary_influence_max_gap'] ?? 0, 2, ',', '.') }} met de hoofdstijl.
        </p>
    </div>

    @if (! empty($scoring['question_breakdown']))
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Per vraag: gekozen 0–1 stijlpunten (gemiddelde bij twee keuzes) / vraaggemiddelde, en vraaggewicht
            </p>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full min-w-[760px] text-xs">
                    <thead>
                        <tr class="text-left uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="pb-2 pr-3">Vraag</th>
                            <th class="pb-2 pr-3">Gewicht</th>
                            @foreach ($styleKeys as $styleKey)
                                <th class="pb-2 pr-3 text-right">{{ \App\Support\QuizStructure::styleLabel($styleKey) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($scoring['question_breakdown'] as $row)
                            <tr class="border-t border-gray-100 dark:border-white/5">
                                <td class="py-1.5 pr-3">{{ $row['question_title'] }}</td>
                                <td class="py-1.5 pr-3">{{ number_format($row['weight'], 2, ',', '.') }}</td>
                                @foreach ($styleKeys as $styleKey)
                                    <td class="py-1.5 pr-3 text-right font-mono">
                                        {{ number_format($row['chosen_scores'][$styleKey] ?? 0, 2, ',', '.') }}
                                        <span class="text-gray-400">/ {{ number_format($row['question_means'][$styleKey] ?? 0, 2, ',', '.') }}</span>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
