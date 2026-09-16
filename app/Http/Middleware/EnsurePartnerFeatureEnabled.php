<?php

namespace App\Http\Middleware;

use App\Models\QuizSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Houdt de volledige partnerfunctie onzichtbaar totdat expliciet aangezet (zie
 * QuizSetting::current()->partner_feature_enabled, beheerd via QuizSettingsPage) — zowel de
 * publieke Blade-pagina's als de JSON-routes 404'en zolang de vlag uit staat, exact zoals het
 * implementatieplan vraagt.
 */
class EnsurePartnerFeatureEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(QuizSetting::current()->partner_feature_enabled, 404);

        return $next($request);
    }
}
