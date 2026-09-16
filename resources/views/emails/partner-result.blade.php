<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Jullie gezamenlijke woonstijl</h1>
        </div>
        <div class="body">
            <p>Beste {{ $link->initiator_name }} &amp; {{ $link->partner_name }},</p>
            <p>Jullie gezamenlijke woonstijladvies staat als PDF bij deze e-mail — met wat jullie delen, waarin jullie verschillen, en een advies dat bij beide stijlen past.</p>
            @if ($siteContent->email_cta_url && $siteContent->email_cta_label)
                <a class="cta" href="{{ $siteContent->email_cta_url }}" target="_blank" rel="noopener">{{ $siteContent->email_cta_label }}</a>
            @endif
        </div>
    </div>
</body>
</html>
