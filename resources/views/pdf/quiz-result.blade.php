<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8" />
<title>Jouw woonstijl</title>
<style>
{{-- 'Clarendon LT Std' wordt niet via @font-face geladen maar rechtstreeks bij dompdf
     geregistreerd, zie QuizResultPdfService::registerClarendonFont() — een CSS @font-face met een
     url() naar een lokaal bestandspad bleek niet betrouwbaar door dompdf's eigen URL-parsing
     herkend te worden. De naam hieronder matcht gewoon wat daar geregistreerd is. --}}
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Clarendon LT Std', Arial, Helvetica, sans-serif;
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
    font-family: 'Clarendon LT Std', Georgia, 'Times New Roman', serif;
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
    font-family: 'Clarendon LT Std', Georgia, 'Times New Roman', serif;
    font-size: 14.5pt;
    font-weight: bold;
    color: #2d2620;
    margin-bottom: 10px;
}

.section-intro {
    color: #4a3526;
    margin-bottom: 12px;
}

.style-row {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
}

.style-row td {
    padding: 4px 0;
    vertical-align: middle;
}

.style-name {
    width: 35%;
    font-weight: bold;
}

.style-percentage {
    width: 12%;
    text-align: right;
    color: #7a5c45;
}

.style-bar-outer {
    width: 100%;
    background: #e8dccf;
    border-radius: 6px;
    height: 10px;
}

.style-bar-inner {
    background: #b7794d;
    height: 10px;
    border-radius: 6px;
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

.accent-colors-title {
    font-size: 10.5pt;
    margin-top: 16px;
    margin-bottom: 8px;
}

.materials-style-title {
    font-size: 10.5pt;
    margin-top: 16px;
    margin-bottom: 8px;
}

.materials-style-title:first-of-type {
    margin-top: 0;
}

.moodboard-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 12px;
    margin-bottom: 4px;
}

.moodboard-table tr {
    page-break-inside: avoid;
}

.moodboard-cell {
    width: 50%;
}

.moodboard-photo,
.moodboard-placeholder {
    display: block;
    width: 100%;
    height: 170px;
    border-radius: 8px;
}

.moodboard-placeholder {
    background: #f0e3d4;
}

.recipe-table {
    width: 100%;
    border-collapse: collapse;
}

.recipe-table td {
    padding: 6px 0;
    vertical-align: top;
    border-bottom: 1px solid #ece1cf;
}

.recipe-label {
    width: 28%;
    font-weight: bold;
    color: #9f6239;
    font-size: 8.5pt;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.recipe-value {
    color: #2d2620;
}

.avoid-box {
    background: #f8f5f1;
    border-radius: 8px;
    padding: 14px 16px;
    color: #4a3526;
}

.page-break {
    page-break-before: always;
}

.footer {
    padding: 20px 44px 0;
    color: #7a5c45;
    font-size: 9pt;
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
    $primaryStyle = $result['primaryStyle'] ?? null;

    // Haalt/bewerkt en cachet PDF-foto's (zie App\Support\PdfImageResolver) — dezelfde ~70
    // antwoordopties en 6 stijlfoto's komen terug in vrijwel elke PDF, dus na de eerste keer per
    // foto komt dit uit een cache i.p.v. steeds opnieuw bij de opslag (mogelijk S3) op te halen.
    // $containRatio (breedte/hoogte) vult de foto aan tot die verhouding zonder 'm uit te rekken
    // of bij te snijden — alleen nodig voor tegels met een vaste hoogte in de layout.
    $pdfImageResolver = app(\App\Support\PdfImageResolver::class);
    $resolveImage = fn (?string $path, ?float $containRatio = null) => $pdfImageResolver->resolve($path, $containRatio);

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
    <div class="cover-title">{{ $result['resultName'] ?? 'Jouw woonstijl' }}</div>
    @if (!empty($primaryStyle['subtitle']))
    <div class="cover-subtitle">{{ $primaryStyle['subtitle'] }}</div>
    @endif
    {{--
        $result['description'] is de door QuizResultTextComposer samengestelde tekst — bij een
        secundaire stijl inclusief de invloed-zin (StyleProfile::advice_secondary, of de generieke
        terugvalzin), exact dezelfde tekst als op de resultatenpagina en in de e-mail. Vroeger stond
        hier $primaryStyle['longDescription'] (de kale stijltekst zonder invloed-zin) met een losse
        "Past ook goed bij jou"-regel eronder — dat noemde de invloedstijl dus twee keer. Bij geen
        primaire stijl (zie QuizResultTextComposer::build()) is er geen $primaryStyle, dan valt dit
        terug op longDescription (leeg) resp. description (de "nog niet genoeg keuzes"-tekst).
    --}}
    <div class="cover-description">{{ $result['description'] ?? ($primaryStyle['longDescription'] ?? '') }}</div>
</div>

@if ($primaryStyle)

@if (!empty($primaryStyle['traits']))
<div class="section">
    <div class="section-title">Dit typeert jouw woonstijl</div>
    @if (!empty($primaryStyle['traitsIntro']))
    <div class="section-intro">{{ $primaryStyle['traitsIntro'] }}</div>
    @endif
    <div class="pill-row">
        @foreach ($primaryStyle['traits'] as $trait)
        <span class="pill">{{ $trait }}</span>
        @endforeach
    </div>
</div>
@endif

@if (!empty($result['personalPalette']))
<div class="section">
    <div class="section-title">{{ !empty($result['basePaletteName']) ? 'Jouw basispalet: '.$result['basePaletteName'] : 'Kleuren ter inspiratie' }}</div>
    @if (!empty($result['colorExplanation']))
    <div class="section-intro">{{ $result['colorExplanation'] }}</div>
    @endif
    <div class="swatch-grid">
        @foreach ($result['personalPalette'] as $color)
        <div class="swatch">
            <div class="swatch-color" style="background: {{ $color['hex'] ?? '#e7ddd1' }};"></div>
            <div class="swatch-name">{{ $color['name'] ?? '' }}</div>
        </div>
        @endforeach
    </div>
    @if (!empty($primaryStyle['colorTip']))
    <div class="tip-box">{{ $primaryStyle['colorTip'] }}</div>
    @endif

    @if (!empty($result['accentColors']))
    <div class="section-title accent-colors-title">Jouw accentkleuren</div>
    <div class="section-intro">Deze accentkleuren geven je gekozen basispalet een persoonlijke uitstraling.</div>
    <div class="swatch-grid">
        @foreach ($result['accentColors'] as $color)
        <div class="swatch">
            <div class="swatch-color" style="background: {{ $color['hex'] ?? '#e7ddd1' }};"></div>
            <div class="swatch-name">{{ $color['name'] ?? '' }}</div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endif

@if (!empty($primaryStyle['materials']) || !empty($primaryStyle['materialsTip']))
{{--
    Toont materiaaladvies (naam-pillen + toelichting) van de primaire stijl, en alléén als de
    bestaande resultaatlogica een tweede stijl als "invloed" heeft aangemerkt
    (QuizScoringService::determineResult(), hetzelfde secondary_style-veld dat
    QuizResultTextComposer gebruikt voor "... met ...-invloeden" in de titel) óók dat van de
    secundaire stijl — geen nieuwe/eigen drempel hier. Een secundaire stijl zonder eigen
    materiaaladvies valt gewoon terug op de enkele-stijl-weergave, zonder de PDF te laten crashen.

    Bewust geen bordfoto meer (zie klantfeedback: een vast, gefotografeerd materialenbord sluit
    niet aan op de afbeeldingen/producten die de bezoeker zelf koos in de test, en kon daardoor
    persoonlijker/misleidender overkomen dan het is — het moodboard hieronder toont wél echt
    gekozen producten). Alleen nog de stijlnaam-pillen en de tekstuele toelichting, dus puur
    algemeen materiaaladvies als inspiratie, geen claim over wat de bezoeker zelf gekozen heeft.
--}}
@php
    $secondaryStyle = $result['secondaryStyle'] ?? null;
    $hasSecondaryMaterials = $secondaryStyle && (!empty($secondaryStyle['materials']) || !empty($secondaryStyle['materialsTip']));
    $materialsBoards = $hasSecondaryMaterials ? [$primaryStyle, $secondaryStyle] : [$primaryStyle];
@endphp
<div class="section page-break">
    @if ($hasSecondaryMaterials)
    <div class="section-title">Materiaalinspiratie bij jouw stijlmix</div>
    <div class="section-intro">Jouw woonstijl combineert elementen van {{ $primaryStyle['label'] }} met invloeden van {{ $secondaryStyle['label'] }}. Hieronder materiaalinspiratie bij beide stijlen, als suggestie en niet per se wat je zelf koos in de test.</div>
    @else
    <div class="section-title">Materiaalinspiratie bij jouw stijl</div>
    <div class="section-intro">Een greep uit materialen die goed bij deze stijl passen, als inspiratie en niet per se wat je zelf koos in de test.</div>
    @endif

    @foreach ($materialsBoards as $board)
        @if ($hasSecondaryMaterials)
        <div class="section-title materials-style-title">{{ $board['label'] }}</div>
        @endif
        @if (!empty($board['materials']))
        <div class="pill-row">
            @foreach ($board['materials'] as $materialName)
            <span class="pill">{{ is_array($materialName) ? ($materialName['name'] ?? '') : $materialName }}</span>
            @endforeach
        </div>
        @endif
        @if (!empty($board['materialsTip']))
        <div class="tip-box">{{ $board['materialsTip'] }}</div>
        @endif
    @endforeach
</div>
@endif

@if (!empty($result['moodboard']))
{{-- Eigen pagina (.page-break): zonder eigen forced break begon dit blok gewoon halverwege de
     pagina waar de materialensectie toevallig ophield, waardoor de tegelrijen daarna nog eens
     onvoorspelbaar over een volgende pagina konden uitlopen — nu begint het moodboard altijd
     schoon bovenaan een nieuwe pagina. Staat vóór "Kies meubels met deze uitstraling" (die zelf
     ook een eigen nieuwe pagina forceert), zodat dat kortere blok niet alvast ruimte inneemt vóór
     het moodboard. 2 (i.p.v. voorheen 3) bredere/hogere tegels per rij — beter zichtbaar dan de
     eerdere kleine tegeltjes. Een <table> i.p.v. inline-block tegels: dompdf's ondersteuning voor
     moderne CSS-layout (flex/grid, en zelfs consistente inline-block-breedtes) is beperkt, een
     tabel geeft hier betrouwbaar precies 2 gelijke kolommen. --}}
<div class="section page-break">
    <div class="section-title">Jouw persoonlijke moodboard</div>
    <table class="moodboard-table">
        @foreach (array_chunk($result['moodboard'], 2) as $row)
        <tr>
            @foreach ($row as $photo)
            {{-- maxWidth voorkomt dat dompdf een veel grotere bron-foto dan nodig moet verwerken
                 (zie PdfImageResolver) — een tegel toont hier nooit breder dan ~340px. --}}
            @php $photoImage = $resolveImage($photo['image'] ?? null, 2.0, 400); @endphp
            <td class="moodboard-cell">
                @if ($photoImage)
                    <img src="{{ $photoImage }}" alt="{{ $photo['title'] ?? '' }}" class="moodboard-photo" />
                @else
                    <div class="moodboard-placeholder"></div>
                @endif
            </td>
            @endforeach
            @if (count($row) === 1)
            <td class="moodboard-cell"></td>
            @endif
        </tr>
        @endforeach
    </table>
</div>
@endif

@if (!empty($primaryStyle['furnitureAdvice']['items']))
<div class="section page-break">
    <div class="section-title">Kies meubels met deze uitstraling</div>
    @if (!empty($primaryStyle['furnitureAdvice']['intro']))
    <div class="section-intro">{{ $primaryStyle['furnitureAdvice']['intro'] }}</div>
    @endif
    <div class="pill-row">
        @foreach ($primaryStyle['furnitureAdvice']['items'] as $item)
        <span class="pill">{{ $item }}</span>
        @endforeach
    </div>
</div>
@endif

@if (!empty($primaryStyle['recipe']))
<div class="section">
    <div class="section-title">Jouw interieurrecept</div>
    <table class="recipe-table">
        @foreach ($primaryStyle['recipe'] as $item)
        <tr>
            <td class="recipe-label">{{ $item['label'] ?? '' }}</td>
            <td class="recipe-value">{{ $item['value'] ?? '' }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endif

@if (!empty($primaryStyle['avoid']))
<div class="section">
    <div class="section-title">Zo kun je met deze stijl verder</div>
    <div class="avoid-box">{{ $primaryStyle['avoid'] }}</div>
</div>
@endif

@else
{{-- Fallback voor oudere inzendingen zonder primaryStyle-data --}}

@if (!empty($result['topStyles']))
<div class="section">
    <div class="section-title">Jouw topstijlen</div>
    @foreach ($result['topStyles'] as $style)
    <table class="style-row">
        <tr>
            <td class="style-name">{{ $style['label'] ?? '' }}</td>
            <td>
                <div class="style-bar-outer">
                    <div class="style-bar-inner" style="width: {{ $style['percentage'] ?? 0 }}%;"></div>
                </div>
            </td>
            <td class="style-percentage">{{ $style['percentage'] ?? 0 }}%</td>
        </tr>
    </table>
    @endforeach
</div>
@endif

@if (!empty($result['traits']))
<div class="section">
    <div class="section-title">Kenmerken van jouw stijl</div>
    <div class="pill-row">
        @foreach ($result['traits'] as $trait)
        <span class="pill">{{ $trait }}</span>
        @endforeach
    </div>
</div>
@endif

@endif

<div class="footer">
    @if (!empty($ctaLabel) && !empty($ctaUrl))
        <div class="footer-intro">Wil je jouw woonstijl vertalen naar jouw eigen woonkamer?</div>
        <a class="cta-button" href="{{ $ctaUrl }}">{{ $ctaLabel }}</a>
    @else
        Wil je jouw woonstijl vertalen naar jouw eigen woonkamer? Plan een interieuradvies bij Boer Staphorst via boer-staphorst.nl/wonen/interieuradvies.
    @endif
</div>

</body>
</html>
