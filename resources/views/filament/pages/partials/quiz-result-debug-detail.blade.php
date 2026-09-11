<div class="space-y-4">
    <div>
        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Berekening</h3>
        <pre class="mt-1 max-h-64 overflow-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ json_encode([
    'primary_style' => $result->primary_style,
    'secondary_style' => $result->secondary_style,
    'tertiary_style' => $result->tertiary_style,
    'primary_strength' => $result->primary_strength,
    'secondary_strength' => $result->secondary_strength,
    'tertiary_strength' => $result->tertiary_strength,
    'case' => $result->case,
    'style_scores' => $result->style_scores,
    'style_percentages' => $result->style_percentages,
    'room_profiles' => $result->room_profiles,
    'dominant_traits' => $result->dominant_traits,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>

    <div>
        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ruwe antwoorden</h3>
        <pre class="mt-1 max-h-40 overflow-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ json_encode($result->answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>

    @foreach ($result->generatedReports as $report)
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                AI-rapport: {{ $report->variant }} ({{ $report->status }})
            </h3>
            @if ($report->error)
                <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $report->error }}</p>
            @endif
            <pre class="mt-1 max-h-64 overflow-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ json_encode($report->output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endforeach
</div>
