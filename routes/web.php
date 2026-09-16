<?php

use App\Http\Controllers\QuizPreviewController;
use App\Http\Controllers\SubmissionPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/admin/submissions/{submission}/pdf', [SubmissionPdfController::class, 'show'])
        ->name('admin.submissions.pdf');
    Route::get('/admin/submissions/{submission}/pdf/download', [SubmissionPdfController::class, 'download'])
        ->name('admin.submissions.pdf.download');

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
