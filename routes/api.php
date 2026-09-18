<?php

use App\Http\Controllers\PartnerComparisonController;
use App\Http\Controllers\PartnerLinkController;
use App\Http\Controllers\QuizConfigController;
use App\Http\Controllers\QuizEventController;
use App\Http\Controllers\QuizLeadController;
use App\Http\Controllers\QuizResultController;
use Illuminate\Support\Facades\Route;

Route::post('/quiz-lead', [QuizLeadController::class, 'handle']);
Route::get('/quiz-lead/{resultUuid}', [QuizLeadController::class, 'status']);
Route::post('/quiz-result', [QuizResultController::class, 'store']);
Route::patch('/quiz-result/{uuid}/base-palette', [QuizResultController::class, 'chooseBasePalette']);
Route::patch('/quiz-result/{uuid}/accent-colors', [QuizResultController::class, 'chooseAccentColors']);
Route::get('/quiz-config', [QuizConfigController::class, 'show']);
Route::post('/quiz-events', [QuizEventController::class, 'store']);

// Partnerfunctie ("Ontdek jullie gezamenlijke woonstijl") — publiek, beveiligd via tokens i.p.v.
// sessies (anonieme bezoekers), staat achter QuizSetting::partner_feature_enabled. Zie het
// implementatieplan "Partnerfunctie" voor de volledige routekaart/beveiligingsredenering.
Route::middleware('partner.feature')->group(function () {
    Route::post('/partner-links', [PartnerLinkController::class, 'create']);
    Route::get('/partner-links/{inviteToken}/preview', [PartnerLinkController::class, 'preview']);
    Route::post('/partner-links/{inviteToken}/claim', [PartnerLinkController::class, 'claim']);
    Route::get('/partner-links/{inviteToken}/status', [PartnerLinkController::class, 'status']);
    Route::patch('/partner-links/{inviteToken}/revoke', [PartnerLinkController::class, 'revoke']);
    Route::patch('/quiz-result/{uuid}/complete-partner', [QuizResultController::class, 'completePartnerResult']);
    Route::get('/partner-comparisons/{accessToken}', [PartnerComparisonController::class, 'show']);
    Route::get('/partner-comparisons/{accessToken}/report', [PartnerComparisonController::class, 'report']);
    Route::post('/partner-comparisons/{accessToken}/mail', [PartnerComparisonController::class, 'mail']);
});
