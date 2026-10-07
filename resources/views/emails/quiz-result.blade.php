<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <title>Jouw woonstijl van Boer Staphorst</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f3efe9;
            font-family: Arial, Helvetica, sans-serif;
            color: #2d2620;
        }

        .wrapper {
            max-width: 600px;
            margin: 32px auto;
            background: #fffdf9;
            border: 1px solid #e7ddd1;
            border-radius: 12px;
            overflow: hidden;
        }

        .header {
            background: #b7794d;
            padding: 32px 36px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 22px;
            line-height: 1.3;
        }

        .body {
            padding: 28px 36px 36px;
        }

        .body p {
            line-height: 1.6;
        }

        .style-pill {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            background: #f0e3d4;
            color: #6b4225;
            font-weight: bold;
            margin: 4px 0 18px;
        }

        .partner-invite {
            margin-top: 28px;
            padding: 20px 22px;
            border-radius: 12px;
            background: #f8f5f1;
            border: 1px solid #e7ddd1;
        }

        .partner-invite h2 {
            margin: 0 0 8px;
            font-size: 17px;
            color: #2d2620;
        }

        .partner-invite p {
            margin: 0 0 14px;
            line-height: 1.55;
            color: #4a3526;
        }

    </style>
</head>
<body>
    @php($partnerInviteUrl = $partnerInviteUrl ?? null)
    {{--
        Outlook desktop (Windows) rendert HTML-mail met Word i.p.v. een browser-engine en negeert
        max-width op een <div> volledig — zonder deze tabel-fallback trekt .wrapper daar open tot
        de volle breedte van het leesvenster i.p.v. als smalle, gecentreerde kaart te verschijnen.
        Alle andere mailclients (incl. mobiel, waar dit al goed werkte) negeren deze
        MSO-conditional-comments en zien gewoon de normale .wrapper-div hieronder.
    --}}
    <!--[if mso]>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0">
    <tr><td>
    <![endif]-->
    <div class="wrapper">
        <div class="header">
            <h1>{{ $siteContent->email_header }}</h1>
        </div>
        <div class="body">
            <p>{{ $siteContent->email_greeting }}{{ $submission->name ? ' ' . $submission->name : '' }},</p>
            <p>{{ $siteContent->email_intro }}</p>
            <p class="style-pill" style="display:inline-block; padding:8px 14px; border-radius:999px; background:#f0e3d4; color:#6b4225; font-weight:bold; margin:4px 0 18px;">{{ $submission->quiz_result['resultName'] ?? '' }}</p>
            <p>{{ $submission->quiz_result['description'] ?? '' }}</p>
            <p>{{ $siteContent->email_outro }}</p>
            {{--
                Knoppen in twee varianten naast elkaar: een VML-tekening (<v:roundrect>) die
                alléén Outlook (desktop/web, de Word-renderer) ziet — dat is de enige manier om
                daar écht ronde hoeken te krijgen, want border-radius ondersteunt die renderer
                nergens, ook niet op een tabelcel — en de gewone tabel/knop die alle andere
                clients (incl. mobiel, waar dit al goed was) blijven zien. De
                "<!--[if !mso]><!-->...<!--<![endif]-->"-constructie is de standaardmanier om iets
                voor Outlook te verbergen: Outlook leest dat als een commentaarblok en slaat het
                over, andere clients begrijpen deze MSO-syntax niet en tonen de inhoud gewoon.
                mso-fit-shape-to-text laat de VML-knop meegroeien met de knoptekst, zodat dit ook
                blijft werken als de (admin-bewerkbare) knoptekst verandert.

                mso-fit-shape-to-text bleek niet overal betrouwbaar (de VML-knop viel terug op een
                minimale breedte en de tekst werd afgeknipt tot "Plan"/"Nodi"/"Deel" i.p.v. de
                volledige tekst) — in plaats daarvan nu een berekende, vaste breedte op basis van
                het aantal tekens, met flinke marge zodat te smal nooit meer voorkomt.
            --}}
            @php($ctaVmlWidth = max(120, mb_strlen($siteContent->email_cta_label) * 9 + 48))
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $siteContent->email_cta_url }}" style="height:44px;width:{{ $ctaVmlWidth }}px;v-text-anchor:middle;margin-top:20px;" arcsize="50%" strokecolor="#b7794d" fillcolor="#b7794d">
            <w:anchorlock/>
            <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;">{{ $siteContent->email_cta_label }}</center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:20px;">
                <tr>
                    <td align="center" bgcolor="#b7794d" style="background:#b7794d; border-radius:999px; padding:12px 20px;">
                        <a href="{{ $siteContent->email_cta_url }}" target="_blank" rel="noopener" style="display:inline-block; color:#ffffff; text-decoration:none; font-weight:bold; font-family:Arial,Helvetica,sans-serif; font-size:14px;">{{ $siteContent->email_cta_label }}</a>
                    </td>
                </tr>
            </table>
            <!--<![endif]-->

            @if ($partnerInviteUrl)
                <div class="partner-invite">
                    <h2>Ontdek jullie gezamenlijke woonstijl</h2>
                    <p>Deel de link hieronder met je partner. Die doet de test net als jij, helemaal zelfstandig, zonder dat jullie elkaars antwoorden zien. Aan het eind krijgt ieder van jullie een eigen persoonlijke woonstijl, plus een gezamenlijk advies over hoe je jullie stijlen goed kunt combineren in huis.</p>
                    {{--
                        Twee correcties puur voor Outlook, binnen dit mso-blok (raakt dus geen
                        andere client): de box heeft zelf 22px linkerpadding, waardoor deze knoppen
                        daar verder naar rechts begonnen dan de "Plan een interieuradvies"-knop
                        erboven — margin-left compenseert dat exact. En een expliciete spacer
                        erna, omdat de VML-tekenobjecten in Outlook niet altijd de volledige
                        onderkant-padding van de box meetellen, waardoor de knoppen tegen de
                        onderrand van de box aan kwamen te staan.
                    --}}
                    <!--[if mso]>
                    <div style="margin-left:-22px;">
                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $partnerInviteUrl }}" style="height:44px;width:190px;v-text-anchor:middle;display:inline-block;" arcsize="50%" strokecolor="#b7794d" fillcolor="#b7794d">
                    <w:anchorlock/>
                    <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;">Nodig je partner uit</center>
                    </v:roundrect>
                    &nbsp;&nbsp;
                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="https://wa.me/?text={{ rawurlencode('Doe je mee met mijn woonstijltest? '.$partnerInviteUrl) }}" style="height:42px;width:165px;v-text-anchor:middle;display:inline-block;" arcsize="50%" strokecolor="#b7794d" fillcolor="#f8f5f1">
                    <w:anchorlock/>
                    <center style="color:#9f6239;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;">Deel via WhatsApp</center>
                    </v:roundrect>
                    </div>
                    <div style="height:20px;line-height:20px;font-size:1px;mso-line-height-rule:exactly;">&nbsp;</div>
                    <![endif]-->
                    <!--[if !mso]><!-->
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td bgcolor="#b7794d" style="background:#b7794d; border-radius:999px; padding:12px 20px;">
                                <a href="{{ $partnerInviteUrl }}" target="_blank" rel="noopener" style="display:inline-block; color:#ffffff; text-decoration:none; font-weight:bold; font-family:Arial,Helvetica,sans-serif; font-size:14px;">Nodig je partner uit</a>
                            </td>
                            <td style="width:10px; line-height:1px; font-size:1px;">&nbsp;</td>
                            <td style="background:transparent; border:1px solid #b7794d; border-radius:999px; padding:11px 20px;">
                                <a
                                    href="https://wa.me/?text={{ rawurlencode('Doe je mee met mijn woonstijltest? '.$partnerInviteUrl) }}"
                                    target="_blank"
                                    rel="noopener"
                                    style="display:inline-block; color:#9f6239; text-decoration:none; font-weight:bold; font-family:Arial,Helvetica,sans-serif; font-size:14px;"
                                >Deel via WhatsApp</a>
                            </td>
                        </tr>
                    </table>
                    <!--<![endif]-->
                </div>
            @endif
        </div>
    </div>
    <!--[if mso]>
    </td></tr>
    </table>
    </td></tr>
    </table>
    <![endif]-->
</body>
</html>
