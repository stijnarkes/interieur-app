<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GenerateController;
use App\Http\Controllers\SendController;

Route::post('/generate', [GenerateController::class, 'handle']);
Route::post('/send', [SendController::class, 'handle']);
