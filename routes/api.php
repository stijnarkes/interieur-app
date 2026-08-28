<?php

use App\Http\Controllers\QuizConfigController;
use App\Http\Controllers\QuizLeadController;
use Illuminate\Support\Facades\Route;

Route::post('/quiz-lead', [QuizLeadController::class, 'handle']);
Route::get('/quiz-config', [QuizConfigController::class, 'show']);
