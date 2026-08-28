@extends('layouts.app')

@section('title', 'Interieurstijltest & Moodboard')

@section('content')
<main class="app-shell" id="quizRoot">
    {{-- Startscherm --}}
    <section class="card quiz-start" id="quizStart">
        @php
            $heroPhotoUrl = \App\Support\QuizImageManifest::url('hero', 'startscherm.webp');
            // +1 voor de kleurvoorkeur-vraag, die als enige geen rij in quiz_questions heeft
            // (zie QuizOptionsPage/remoteConfig.js) — zo blijft dit aantal kloppen zodra een
            // admin later zelf vragen toevoegt of verwijdert.
            $totalQuestions = \App\Models\QuizQuestion::count() + 1;
        @endphp
        @if ($heroPhotoUrl)
            <div class="quiz-start-photo">
                <img src="{{ $heroPhotoUrl }}" alt="" />
            </div>
        @endif

        <h1>Ontdek jouw woonstijl</h1>
        <p>Kies jouw favorieten en ontdek in een paar minuten welke stijl, kleuren en meubels bij jou passen.</p>

        <div class="actions">
            <button type="button" class="btn btn-primary" id="startQuizBtn">Start de stijlanalyse</button>
        </div>

        <div class="quiz-start-facts">
            <span class="quiz-start-fact">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                &plusmn; 3 minuten
            </span>
            <span class="quiz-start-fact">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 4.9.75c0 1.75-2.4 2.25-2.4 2.25"/><line x1="12" y1="16.5" x2="12.01" y2="16.5"/></svg>
                {{ $totalQuestions }} vragen
            </span>
        </div>
    </section>

    {{-- Hoofdvoortgang: alleen zichtbaar op de overgangsschermen tussen onderdelen en op de
         resultaatpagina (waar "Jouw woonstijl" als bereikte derde stap oplicht) — tijdens het
         beantwoorden van losse vragen blijft 'ie verborgen, dat toont de kaart zelf al
         ("Vraag X van Y"), dubbele voortgangsindicatoren voegden daar niets aan toe. --}}
    <section id="stepperWrap" hidden>
        <div id="sectionStepperMount"></div>
    </section>

    {{-- Onderdelen: overgangsschermen + quizstappen --}}
    <section id="quizJourney" hidden>
        <div class="quiz-transition-card" id="quizTransition" hidden></div>

        <section class="card quiz-step-card" id="quizSteps" hidden>
            <div id="quizProgressMount"></div>

            <div id="quizStepMount"></div>

            <div class="actions quiz-nav">
                <button type="button" class="btn btn-secondary" id="quizBackBtn">Terug</button>
                <button type="button" class="btn btn-primary" id="quizNextBtn" disabled>Volgende</button>
            </div>
        </section>
    </section>

    {{-- Resultaat --}}
    <section class="card results report" id="quizResult" hidden>
        <div class="results-head">
            <h2>Jouw persoonlijke woonstijl</h2>
            <button type="button" class="btn btn-link" id="restartQuizBtn">Opnieuw beginnen</button>
        </div>

        {{-- Alleen het heldenblok (herkenning) wordt hier getoond — zie styleResult.js. De
             uitgebreide analyse (kenmerken, kleuren, materialen, meubeladvies, moodboard,
             interieurrecept, nuance) is gereserveerd voor het PDF-rapport per e-mail. --}}
        <div id="styleResultMount"></div>

        <div id="reportTeaserMount"></div>

        <section class="cta card">
            <div id="quizLeadMount"></div>
        </section>
    </section>
</main>
@endsection
