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
                Knoppen als tabel i.p.v. een <a> met padding: Outlook desktop/web rendert met
                Word i.p.v. een browser-engine en past padding nooit toe op een inline-element als
                <a>, ook niet als die padding inline (i.p.v. via een class) staat — dat is een
                structurele beperking van die renderer, geen genegeerde CSS-regel. Een tabelcel
                (<td>) ondersteunt padding daar wél, dus de padding/achtergrond/afronding staan nu
                op de <td>, en de <a> erin is alleen nog de klikbare tekst. Afgeronde hoeken
                (border-radius) blijven daar wel vierkant — dat ondersteunt die renderer nergens,
                ook niet op een tabelcel — maar dat is een geaccepteerde, overal gangbare
                beperking; de belangrijkste winst is dat de tekst niet meer tegen de rand plakt.
            --}}
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:20px;">
                <tr>
                    <td align="center" bgcolor="#b7794d" style="background:#b7794d; border-radius:999px; padding:12px 20px;">
                        <a href="{{ $siteContent->email_cta_url }}" target="_blank" rel="noopener" style="display:inline-block; color:#ffffff; text-decoration:none; font-weight:bold; font-family:Arial,Helvetica,sans-serif; font-size:14px;">{{ $siteContent->email_cta_label }}</a>
                    </td>
                </tr>
            </table>

            @if ($partnerInviteUrl)
                <div class="partner-invite">
                    <h2>Ontdek jullie gezamenlijke woonstijl</h2>
                    <p>Deel de link hieronder met je partner. Die doet de test net als jij, helemaal zelfstandig, zonder dat jullie elkaars antwoorden zien. Aan het eind krijgt ieder van jullie een eigen persoonlijke woonstijl, plus een gezamenlijk advies over hoe je jullie stijlen goed kunt combineren in huis.</p>
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
