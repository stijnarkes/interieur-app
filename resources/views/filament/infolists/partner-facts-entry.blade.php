@php
    // De opgeslagen facts bevatten nog de ruwe style_key's (zie PartnerComparisonService) — pas
    // vertalen naar leesbare labels op het moment van tonen, zelfde als PartnerComparisonController
    // en PartnerReportPdfService dat doen.
    $styleLabel = fn (?string $key) => $key ? (\App\Models\StyleProfile::forStyle($key)?->label ?? $key) : $key;
    $facts = \App\Support\PartnerFactPresenter::resolveStyleKeys($getState() ?? [], $styleLabel);
    $similarities = collect($facts['similarities'])->map(fn (array $fact) => \App\Support\PartnerFactPresenter::describe($fact))->filter();
    $differences = collect($facts['differences'])->map(fn (array $fact) => \App\Support\PartnerFactPresenter::describe($fact))->filter();
@endphp

@if ($similarities->isEmpty() && $differences->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">Nog geen vergelijking beschikbaar.</p>
@else
    <div style="display: flex; flex-direction: column; gap: 12px;">
        @if ($similarities->isNotEmpty())
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Wat ze delen</p>
                <ul class="mt-1 list-disc pl-5 text-sm text-gray-900 dark:text-white">
                    @foreach ($similarities as $text)
                        <li>{{ $text }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($differences->isNotEmpty())
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Waarin ze verschillen</p>
                <ul class="mt-1 list-disc pl-5 text-sm text-gray-900 dark:text-white">
                    @foreach ($differences as $text)
                        <li>{{ $text }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
