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
    </style>
</head>
<body>
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
        </div>
    </div>
</body>
</html>
