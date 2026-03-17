<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GenerateController;
use App\Http\Controllers\GenerationStatusController;
use App\Http\Controllers\SendController;

Route::post('/generate', [GenerateController::class, 'handle']);
Route::get('/generations/{generation}', [GenerationStatusController::class, 'show']);
Route::post('/send', [SendController::class, 'handle']);
