<?php

use App\Http\Controllers\QuizConfigController;
use App\Http\Controllers\QuizLeadController;
use App\Http\Controllers\QuizResultController;
use Illuminate\Support\Facades\Route;

Route::post('/quiz-lead', [QuizLeadController::class, 'handle']);
Route::post('/quiz-result', [QuizResultController::class, 'store']);
Route::get('/quiz-config', [QuizConfigController::class, 'show']);
