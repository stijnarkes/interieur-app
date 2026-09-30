@php
    $record = $getRecord();
    $scoring = app(\App\Services\QuizScoringService::class)->explain($record->quiz_answers ?? []);
    $primaryStyle = $scoring['primary_style'];

    $evidence = collect($scoring['question_breakdown'] ?? [])
        ->filter(fn (array $row): bool => $primaryStyle && ($row['contribution'][$primaryStyle] ?? 0) > 0)
        ->sortByDesc(fn (array $row) => $row['contribution'][$primaryStyle])
        ->take(3)
        ->values();
@endphp

@if (! $primaryStyle || $evidence->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Geen enkele gekozen afbeelding droeg positief bij aan deze uitslag.
    </p>
@else
    <div class="space-y-4">
        <p class="text-sm text-gray-700 dark:text-gray-300">
            Deze keuzes pasten het best bij {{ \App\Support\QuizStructure::styleLabel($primaryStyle) }}:
        </p>

        @foreach ($evidence as $item)
            <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0 dark:border-white/5">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $item['question_title'] }}
                </p>
                <div class="mt-2 flex flex-wrap gap-4">
                    @foreach ($item['chosen_options'] as $option)
                        <div class="flex items-center gap-3">
                            <div class="relative shrink-0" style="width: 72px; height: 72px;">
                                <img
                                    src="{{ $option->publicImageUrl() }}"
                                    alt="{{ $option->title }}"
                                    style="width: 72px; height: 72px; object-fit: cover;"
                                    class="rounded-lg bg-gray-100 dark:bg-gray-800"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                />
                                <div
                                    class="hidden items-center justify-center rounded-lg bg-gray-100 text-center text-[10px] leading-tight text-gray-400 dark:bg-gray-800"
                                    style="width: 72px; height: 72px;"
                                >Geen afbeelding</div>
                            </div>
                            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $option->title }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
