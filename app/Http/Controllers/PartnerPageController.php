<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Serveert de twee publieke, bookmarkbare partnerpagina's (zie het implementatieplan, sectie
 * "Routes") — beide zijn pure Blade-schillen: alle daadwerkelijke inhoud/validatie gebeurt
 * client-side via resources/js/partner.js tegen de JSON-routes in routes/api.php. Dit blijven
 * expres losse, kale pagina's i.p.v. iets op de bestaande /-route te stapelen (zie
 * QuizResultController: de individuele quiz heeft geen bookmarkbare resultaat-URL, dit moest er
 * voor de partnerfunctie wél expliciet bijkomen).
 */
class PartnerPageController extends Controller
{
    public function invite(string $inviteToken): View
    {
        return view('partner.invite', ['inviteToken' => $inviteToken]);
    }

    public function result(string $accessToken): View
    {
        return view('partner.result', ['accessToken' => $accessToken]);
    }
}
