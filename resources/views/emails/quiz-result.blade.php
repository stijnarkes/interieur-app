<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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

        .cta {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            border-radius: 999px;
            background: #b7794d;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: bold;
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

        .partner-invite .cta {
            margin-top: 0;
            margin-right: 10px;
        }

        .partner-invite .cta-outline {
            display: inline-block;
            margin-top: 10px;
            padding: 11px 20px;
            border-radius: 999px;
            border: 1px solid #b7794d;
            color: #9f6239 !important;
            text-decoration: none;
            font-weight: bold;
            background: transparent;
        }
    </style>
</head>
<body>
    @php($partnerInviteUrl = $partnerInviteUrl ?? null)
    <div class="wrapper">
        <div class="header">
            <h1>{{ $siteContent->email_header }}</h1>
        </div>
        <div class="body">
            <p>{{ $siteContent->email_greeting }}{{ $submission->name ? ' ' . $submission->name : '' }},</p>
            <p>{{ $siteContent->email_intro }}</p>
            <p class="style-pill">{{ $submission->quiz_result['resultName'] ?? '' }}</p>
            <p>{{ $submission->quiz_result['description'] ?? '' }}</p>
            <p>{{ $siteContent->email_outro }}</p>
            <a class="cta" href="{{ $siteContent->email_cta_url }}" target="_blank" rel="noopener">{{ $siteContent->email_cta_label }}</a>

            @if ($partnerInviteUrl)
                <div class="partner-invite">
                    <h2>Ontdek jullie gezamenlijke woonstijl</h2>
                    <p>Deel de link hieronder met je partner. Die doet de test net als jij, helemaal zelfstandig en zonder elkaars antwoorden te zien. Aan het eind krijgen jullie allebei niet alleen je eigen persoonlijke woonstijl, maar ook een gezamenlijk advies over hoe jullie beider stijlen slim met elkaar te combineren zijn in huis.</p>
                    <a class="cta" href="{{ $partnerInviteUrl }}" target="_blank" rel="noopener">Nodig je partner uit</a>
                    <a
                        class="cta-outline"
                        href="https://wa.me/?text={{ rawurlencode('Doe je mee met mijn woonstijltest? '.$partnerInviteUrl) }}"
                        target="_blank"
                        rel="noopener"
                    >Deel via WhatsApp</a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
