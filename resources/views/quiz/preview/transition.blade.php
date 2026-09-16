@extends('layouts.app')

@section('title', 'Voorbeeld — ' . $section['title'])

@section('content')
<main class="app-shell">
    <div class="quiz-preview-banner card">
        <p>Dit is een voorbeeld van dit overgangsscherm zoals bezoekers 'm zien — er wordt niets opgeslagen.</p>
        <a href="{{ route('filament.admin.pages.teksten') }}" class="btn btn-secondary">&larr; Terug naar Teksten</a>
    </div>

    <div
        id="quizPreviewRoot"
        class="quiz-transition-card"
        data-preview="{{ json_encode([
            'type' => 'transition',
            'section' => $section,
            'sectionIndex' => $sectionIndex,
            'totalSections' => $totalSections,
        ]) }}"
    ></div>
</main>
@endsection
