<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Jouw interieuradvies van Boer Staphorst</title>
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

        .header p {
            margin: 8px 0 0;
            color: #fce8d7;
            font-size: 14px;
        }

        .body {
            padding: 32px 36px;
        }

        .body p {
            margin: 0 0 16px;
            font-size: 15px;
            line-height: 1.6;
            color: #2d2620;
        }

        .body p:last-child {
            margin-bottom: 0;
        }

        .highlight-box {
            background: #f7ece0;
            border-left: 4px solid #b7794d;
            border-radius: 6px;
            padding: 14px 18px;
            margin: 20px 0;
            font-size: 14px;
            color: #5a3e2b;
        }

        .footer {
            background: #f3efe9;
            border-top: 1px solid #e7ddd1;
            padding: 20px 36px;
            text-align: center;
        }

        .footer p {
            margin: 0;
            font-size: 12px;
            color: #6f6458;
            line-height: 1.6;
        }

        .footer a {
            color: #b7794d;
            text-decoration: none;
        }

        .footer strong {
            color: #2d2620;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Jouw interieuradvies is klaar</h1>
            <p>Persoonlijk samengesteld door Boer Staphorst Interieuradvies</p>
        </div>

        <div class="body">
            @if($submission->name)
                <p>Beste {{ $submission->name }},</p>
            @else
                <p>Beste,</p>
            @endif

            <p>
                Bedankt voor het gebruik van de interieuradviestool van Boer Staphorst.
                We hebben jouw persoonlijke interieuradvies voor je samengesteld op basis van jouw stijlkeuzes en voorkeuren.
            </p>

            <div class="highlight-box">
                Jouw persoonlijk interieuradvies is bijgevoegd als PDF bij deze e-mail.
                Open het bijgevoegde bestand <strong>interieuradvies.pdf</strong> om je volledige advies te bekijken,
                inclusief moodboard, kleurenpalet, materiaaladvies en productideeën.
            </div>

            <p>
                Wil je dit advies verder laten uitwerken of heb je vragen? Ons team van interieurstylistens staat
                voor je klaar. Bezoek ons in de winkel of neem contact op via onze website.
            </p>

            <p>
                Wij hopen dat dit advies je inspireert bij het inrichten van jouw mooie thuis.
            </p>

            <p>Met vriendelijke groet,<br>
            <strong>Het team van Boer Staphorst</strong></p>
        </div>

        <div class="footer">
            <p>
                <strong>Boer Staphorst Interieuradvies</strong><br>
                Dit bericht is automatisch gegenereerd. Stijlkeuze: <em>{{ $submission->style }}</em>
            </p>
        </div>
    </div>
</body>
</html>
