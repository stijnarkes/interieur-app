<?php

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
});
