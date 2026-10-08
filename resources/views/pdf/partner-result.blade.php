<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8" />
<title>Jullie gezamenlijke woonstijl</title>
<style>
{{-- Exact dezelfde stijlregels als resources/views/pdf/quiz-result.blade.php — bewust geen
     nieuwe visuele taal voor de partnerfunctie (zie het implementatieplan, sectie 8). --}}
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Arial, Helvetica, sans-serif;
    color: #2d2620;
    background: #ffffff;
    font-size: 10.5pt;
    line-height: 1.6;
}

.cover {
    background: #f5ede2;
    padding: 42px 44px 36px 44px;
    border-bottom: 4px solid #b7794d;
    margin-bottom: 24px;
}

.pdf-logo {
    display: block;
    height: 20pt;
    margin-bottom: 10px;
}

.brand-label {
    font-size: 7.5pt;
    color: #b7794d;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    margin-bottom: 18px;
}

.cover-title {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 26pt;
    font-weight: bold;
    color: #2d2626;
    line-height: 1.2;
    margin-bottom: 10px;
}

.cover-subtitle {
    font-size: 11.5pt;
    color: #9f6239;
    font-weight: bold;
    margin-bottom: 10px;
}

.cover-description {
    font-size: 10.5pt;
    color: #4a3526;
}

.section {
    padding: 0 44px;
    margin-bottom: 26px;
}

.section-title {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 14.5pt;
    font-weight: bold;
    color: #2d2620;
    margin-bottom: 10px;
}

.section-intro {
    color: #4a3526;
    margin-bottom: 12px;
}

.pill-row {
    width: 100%;
}

.pill {
    display: inline-block;
    padding: 5px 12px;
    margin: 0 6px 8px 0;
    border-radius: 999px;
    background: #f0e3d4;
    color: #6b4225;
    font-size: 9.5pt;
    font-weight: bold;
}

.tip-box {
    background: #f8f5f1;
    border: 1px solid #e7ddd1;
    border-radius: 8px;
    padding: 10px 14px;
    color: #4a3526;
    font-size: 9.5pt;
    margin-top: 10px;
    /* Voorkomt dat een los adviesblokje halverwege over een paginagrens heen knipt. */
    page-break-inside: avoid;
}

.swatch-grid {
    width: 100%;
}

.swatch {
    display: inline-block;
    width: 17.5%;
    margin: 0 1.5% 10px 0;
    text-align: center;
    vertical-align: top;
}

.swatch-color {
    width: 100%;
    height: 46px;
    border-radius: 8px;
    border: 1px solid rgba(45, 38, 32, 0.15);
    margin-bottom: 6px;
}

.swatch-name {
    font-size: 8.5pt;
    font-weight: bold;
    color: #2d2620;
}

.footer {
    padding: 20px 44px 0;
    color: #7a5c45;
    font-size: 9pt;
}

.footer-heading {
    font-weight: bold;
    font-size: 12.5pt;
    color: #2d2620;
    margin-bottom: 8px;
}

.footer-intro {
    margin-bottom: 10px;
}

.cta-button {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 999px;
    background: #b7794d;
    color: #ffffff;
    text-decoration: none;
    font-weight: bold;
    font-size: 9.5pt;
}

.compare-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 12px 0;
    margin-bottom: 8px;
}

.compare-cell {
    width: 50%;
    vertical-align: top;
    background: #f8f5f1;
    border-radius: 8px;
    padding: 12px 14px;
}

.compare-name {
    font-weight: bold;
    color: #2d2620;
    margin-bottom: 4px;
}

.compare-style {
    color: #9f6239;
    font-size: 9.5pt;
}

.compare-secondary {
    color: #6b4225;
    font-size: 8.5pt;
    margin-top: 2px;
}

.page-mark {
    /* position: fixed laat dompdf dit op elke pagina herhalen (zie dompdf's ondersteuning voor
       "running" headers/footers) — niet afhankelijk van waar dit element in de HTML staat. */
    position: fixed;
    bottom: 24px;
    right: 44px;
    width: 46px;
}
</style>
</head>
<body>

@php
    // Statisch, meegeleverde bestanden (geen S3/QuizImageManifest-content), dus gewoon lokaal
    // inlezen — geen cache nodig, dit zijn geen door de admin geüploade foto's die kunnen wijzigen.
    $logoBase64 = base64_encode(file_get_contents(public_path('images/branding/boer-staphorst-logo.png')));
    $markBase64 = base64_encode(file_get_contents(public_path('images/branding/boer-logo-pdf-footer.png')));
@endphp

<div class="page-mark">
    <img src="data:image/png;base64,{{ $markBase64 }}" alt="" style="width: 100%; display: block;" />
</div>

<div class="cover">
    <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Boer Staphorst" class="pdf-logo" />
    <div class="brand-label">Woondroomtest</div>
    <div class="cover-title">Jullie gezamenlijke woonstijl</div>
    <div class="cover-subtitle">{{ $initiatorName }} &amp; {{ $partnerName }}</div>
    <div class="cover-description">Een overzicht van wat jullie delen én waarin jullie verschillen, met een advies dat bij beide stijlen past.</div>
</div>

<div class="section">
    <div class="section-title">Jullie stijlen</div>
    <table class="compare-table">
        <tr>
            <td class="compare-cell">
                <div class="compare-name">{{ $initiatorName }}</div>
                <div class="compare-style">{{ $initiatorStyleLabel ?? 'Onbekend' }}</div>
                @if (!empty($initiatorSecondaryStyleLabel))
                <div class="compare-secondary">Invloed: {{ $initiatorSecondaryStyleLabel }}</div>
                @endif
            </td>
            <td class="compare-cell">
                <div class="compare-name">{{ $partnerName }}</div>
                <div class="compare-style">{{ $partnerStyleLabel ?? 'Onbekend' }}</div>
                @if (!empty($partnerSecondaryStyleLabel))
                <div class="compare-secondary">Invloed: {{ $partnerSecondaryStyleLabel }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>

@if (!empty($initiatorPalette) || !empty($partnerPalette))
<div class="section">
    <div class="section-title">Jullie basiskleuren</div>
    @if (!empty($initiatorPalette))
        <div class="section-intro">{{ $initiatorName }}</div>
        <div class="swatch-grid">
            @foreach ($initiatorPalette as $color)
            <div class="swatch">
                <div class="swatch-color" style="background: {{ $color['hex'] ?? '#e7ddd1' }};"></div>
                <div class="swatch-name">{{ $color['name'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
    @endif
    @if (!empty($partnerPalette))
        <div class="section-intro">{{ $partnerName }}</div>
        <div class="swatch-grid">
            @foreach ($partnerPalette as $color)
            <div class="swatch">
                <div class="swatch-color" style="background: {{ $color['hex'] ?? '#e7ddd1' }};"></div>
                <div class="swatch-name">{{ $color['name'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endif

@if (!empty($initiatorAccentColors) || !empty($partnerAccentColors))
<div class="section">
    <div class="section-title">Jullie accentkleuren</div>
    @if (!empty($initiatorAccentColors))
        <div class="section-intro">{{ $initiatorName }}</div>
        <div class="swatch-grid">
            @foreach ($initiatorAccentColors as $color)
            <div class="swatch">
                <div class="swatch-color" style="background: {{ $color['hex'] ?? '#e7ddd1' }};"></div>
                <div class="swatch-name">{{ $color['name'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
    @endif
    @if (!empty($partnerAccentColors))
        <div class="section-intro">{{ $partnerName }}</div>
        <div class="swatch-grid">
            @foreach ($partnerAccentColors as $color)
            <div class="swatch">
                <div class="swatch-color" style="background: {{ $color['hex'] ?? '#e7ddd1' }};"></div>
                <div class="swatch-name">{{ $color['name'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endif

@if (!empty($similarities))
<div class="section">
    <div class="section-title">Wat jullie delen</div>
    <div class="pill-row">
        @foreach ($similarities as $text)
        <span class="pill">{{ $text }}</span>
        @endforeach
    </div>
</div>
@endif

@if (!empty($differences))
<div class="section">
    <div class="section-title">Waarin jullie verschillen</div>
    <div class="pill-row">
        @foreach ($differences as $text)
        <span class="pill">{{ $text }}</span>
        @endforeach
    </div>
</div>
@endif

@if (!empty($suggestions['title']))
<div class="section">
    <div class="section-title">{{ $suggestions['title'] }}</div>
    @if (!empty($suggestions['intro']))
        <div class="section-intro">{{ $suggestions['intro'] }}</div>
    @endif
    @if (!empty($suggestions['basisTip']))
        <div class="tip-box"><strong>Basis:</strong> {{ $suggestions['basisTip'] }}</div>
    @endif
    @if (!empty($suggestions['materialsTip']))
        <div class="tip-box"><strong>Materialen:</strong> {{ $suggestions['materialsTip'] }}</div>
    @endif
    @if (!empty($suggestions['accentTip']))
        <div class="tip-box"><strong>Accentkleuren:</strong> {{ $suggestions['accentTip'] }}</div>
    @endif
</div>
@endif

<div class="footer">
    <div class="footer-heading">Samen maken we jullie woondromen waar!</div>
    <div class="footer-intro">Volop ideeën opgedaan, maar weten jullie nog niet precies hoe jullie ze kunnen toepassen? Geen zorgen! Onze interieurstylistes helpen jullie graag om van jullie woonideeën een mooi en persoonlijk plan te maken.</div>
    @if (!empty($ctaLabel) && !empty($ctaUrl))
        <a class="cta-button" href="{{ $ctaUrl }}">{{ $ctaLabel }}</a>
    @else
        Plan een interieuradvies bij Boer Staphorst via boer-staphorst.nl/wonen/interieuradvies.
    @endif
</div>

</body>
</html>
