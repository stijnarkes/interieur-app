@extends('layouts.app', ['viteEntries' => ['resources/js/partner.js']])

@section('title', 'Uitnodiging — Gezamenlijke woonstijltest')

@section('content')
<div id="partnerInviteRoot" data-invite-token="{{ $inviteToken }}">
    <div class="app-shell" id="partnerInviteLandingShell">
        <section class="card" id="partnerInviteLanding">
            <div id="partnerInviteLandingMount"></div>
        </section>
    </div>

    {{-- Bevat de volledig geïsoleerde partnertest — pas zichtbaar zodra de uitnodiging
         daadwerkelijk geclaimd is (zie resources/js/partner/invitePage.js). Hergebruikt precies
         dezelfde quiz-DOM/vragen/scorelogica als de individuele test, alleen met een
         partnerClaimToken meegestuurd naar /api/quiz-result — zie initQuiz() in
         resources/js/quiz/quiz.js. --}}
    <div id="partnerInviteQuizWrap" hidden>
        @include('partials.quiz-app')
    </div>
</div>
@endsection
