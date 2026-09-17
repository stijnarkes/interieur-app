<?php

use App\Http\Controllers\Admin\PartnerLinkPdfController;
use App\Http\Controllers\PartnerPageController;
use App\Http\Controllers\QuizPreviewController;
use App\Http\Controllers\SubmissionPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Partnerfunctie ("Ontdek jullie gezamenlijke woonstijl") — publieke, bookmarkbare pagina's, staan
// achter dezelfde feature-vlag als de bijbehorende JSON-routes (routes/api.php).
Route::middleware('partner.feature')->group(function () {
    Route::get('/gezamenlijk/uitnodiging/{inviteToken}', [PartnerPageController::class, 'invite'])
        ->name('partner.invite');
    Route::get('/gezamenlijk/{accessToken}', [PartnerPageController::class, 'result'])
        ->name('partner.result');
});

Route::middleware('auth')->group(function () {
    Route::get('/admin/submissions/{submission}/pdf', [SubmissionPdfController::class, 'show'])
        ->name('admin.submissions.pdf');
    Route::get('/admin/submissions/{submission}/pdf/download', [SubmissionPdfController::class, 'download'])
        ->name('admin.submissions.pdf.download');

    Route::get('/admin/partner-links/{partnerLink}/pdf', [PartnerLinkPdfController::class, 'show'])
        ->name('admin.partner-links.pdf');
    Route::get('/admin/partner-links/{partnerLink}/pdf/download', [PartnerLinkPdfController::class, 'download'])
        ->name('admin.partner-links.pdf.download');

    // Alleen-lezen voorbeeldweergave voor de admin (Antwoordopties/Teksten) — zie
    // QuizPreviewController voor waarom dit nooit iets opslaat/verstuurt.
    Route::get('/admin/voorbeeld/vraag/{question:question_key}', [QuizPreviewController::class, 'question'])
        ->name('quiz.preview.question');
    Route::get('/admin/voorbeeld/overgangsscherm/{sectionId}', [QuizPreviewController::class, 'transition'])
        ->name('quiz.preview.transition');
    Route::get('/admin/voorbeeld/resultaat', [QuizPreviewController::class, 'result'])
        ->name('quiz.preview.result');
    Route::get('/admin/voorbeeld/email', [QuizPreviewController::class, 'email'])
        ->name('quiz.preview.email');
});
