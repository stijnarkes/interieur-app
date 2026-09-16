@extends('layouts.app')

@section('title', 'Voorbeeld — Resultatenpagina')

@section('content')
<main class="app-shell">
    <div class="quiz-preview-banner card">
        <p>Dit is een voorbeeld van de resultatenpagina — er wordt niets opgeslagen of verzonden, ook niet als je het leadformulier of de accentkleurenstap hieronder probeert.</p>
        <a href="{{ route('filament.admin.pages.teksten') }}" class="btn btn-secondary">&larr; Terug naar Teksten</a>
    </div>

    <form method="GET" class="quiz-preview-style-picker card">
        <div class="field">
            <label for="previewStyle">Basisstijl</label>
            <select name="style" id="previewStyle">
                @foreach ($styles as $style)
                    <option value="{{ $style->style_key }}" @selected($style->style_key === $selectedPrimaryKey)>{{ $style->label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="previewSecondary">Invloed (optioneel)</label>
            <select name="secondary" id="previewSecondary">
                <option value="">Geen</option>
                @foreach ($styles as $style)
                    <option value="{{ $style->style_key }}" @selected($style->style_key === $selectedSecondaryKey)>{{ $style->label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Bekijken</button>
    </form>

    <section
        class="card results report"
        id="quizPreviewRoot"
        data-preview="{{ json_encode(['type' => 'result', 'result' => $result]) }}"
    >
        <div id="styleResultMount"></div>
        <div id="accentColorMount"></div>
        <div id="reportTeaserMount"></div>

        <section class="cta card" id="quizLeadCard">
            <div id="quizLeadMount"></div>
        </section>
    </section>
</main>
@endsection
