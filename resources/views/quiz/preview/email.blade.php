@extends('layouts.app')

@section('title', 'Voorbeeld — Bevestigingsmail')

@section('content')
<main class="app-shell">
    <div class="quiz-preview-banner card">
        <p>Dit is een voorbeeld van de bevestigingsmail — er wordt niets verzonden.</p>
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

    <iframe class="quiz-preview-email-frame" srcdoc="{{ $emailHtml }}" title="Voorbeeld van de bevestigingsmail"></iframe>
</main>
@endsection
