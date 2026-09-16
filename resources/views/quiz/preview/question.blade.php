@extends('layouts.app')

@section('title', 'Voorbeeld — ' . $question['title'])

@section('content')
<main class="app-shell">
    <div class="quiz-preview-banner card">
        <p>Dit is een voorbeeld van deze vraag zoals bezoekers 'm zien — er wordt niets opgeslagen.</p>
        <a href="{{ route('filament.admin.pages.quiz-opties') }}" class="btn btn-secondary">&larr; Terug naar Antwoordopties</a>
    </div>

    <section id="quizJourney">
        <section class="card quiz-step-card">
            <div id="quizPreviewRoot" data-preview="{{ json_encode(['type' => 'question', 'question' => $question]) }}"></div>
        </section>
    </section>
</main>
@endsection
