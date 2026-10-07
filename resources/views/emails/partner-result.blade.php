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
    <title>Jullie gezamenlijke woonstijl van Boer Staphorst</title>
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
    </style>
</head>
<body>
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
            <h1>Jullie gezamenlijke woonstijl</h1>
        </div>
        <div class="body">
            <p>Beste{{ $recipientName ? ' '.$recipientName : '' }},</p>
            <p>Jullie gezamenlijke woonstijladvies staat als PDF bij deze e-mail, met wat jullie delen, waarin jullie verschillen, en een advies dat bij beide stijlen past.</p>
            {{--
                Knop als tabel i.p.v. een <a> met padding: Outlook desktop/web rendert met Word
                i.p.v. een browser-engine en past padding nooit toe op een inline-element als <a>,
                ook niet als die padding inline (i.p.v. via een class) staat — dat is een
                structurele beperking van die renderer, geen genegeerde CSS-regel. Een tabelcel
                (<td>) ondersteunt padding daar wél, dus de padding/achtergrond/afronding staan nu
                op de <td>, en de <a> erin is alleen nog de klikbare tekst. Afgeronde hoeken
                (border-radius) blijven daar wel vierkant — dat ondersteunt die renderer nergens,
                ook niet op een tabelcel — maar dat is een geaccepteerde, overal gangbare
                beperking; de belangrijkste winst is dat de tekst niet meer tegen de rand plakt.
            --}}
            @if ($siteContent->email_cta_url && $siteContent->email_cta_label)
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:20px;">
                    <tr>
                        <td align="center" bgcolor="#b7794d" style="background:#b7794d; border-radius:999px; padding:12px 20px;">
                            <a href="{{ $siteContent->email_cta_url }}" target="_blank" rel="noopener" style="display:inline-block; color:#ffffff; text-decoration:none; font-weight:bold; font-family:Arial,Helvetica,sans-serif; font-size:14px;">{{ $siteContent->email_cta_label }}</a>
                        </td>
                    </tr>
                </table>
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
