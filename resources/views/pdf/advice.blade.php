<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8" />
<title>Jouw Persoonlijk Interieuradvies</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Arial, Helvetica, sans-serif;
    color: #2d2620;
    background: #ffffff;
    font-size: 10.5pt;
    line-height: 1.6;
}

/* ─── Page wrapper ─── */
.page { padding: 0; }

/* ─── Cover header ─── */
.cover {
    background: #f5ede2;
    padding: 42px 44px 36px 44px;
    border-bottom: 4px solid #b7794d;
    margin-bottom: 0;
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
    font-size: 26pt;
    font-weight: bold;
    color: #2d2620;
    line-height: 1.15;
    margin-bottom: 6px;
}

.cover-tagline {
    font-size: 10.5pt;
    color: #7a5c45;
    margin-bottom: 20px;
    font-style: italic;
}

.cover-name {
    font-size: 11pt;
    color: #4a3526;
    margin-bottom: 16px;
}

.cover-name strong {
    font-size: 12pt;
    color: #2d2620;
}

/* Meta summary row */
.meta-row {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
}

.meta-row td {
    padding: 10px 14px;
    vertical-align: top;
    width: 25%;
}

.meta-cell {
    background: #ffffff;
    border: 1px solid #ddd0c0;
    border-radius: 6px;
    padding: 10px 14px;
}

.meta-label {
    font-size: 7pt;
    color: #b7794d;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-weight: bold;
    margin-bottom: 4px;
}

.meta-value {
    font-size: 9.5pt;
    color: #2d2620;
    font-weight: bold;
}

/* ─── Content area ─── */
.content {
    padding: 36px 44px;
}

/* ─── Section ─── */
.section {
    margin-bottom: 32px;
}

.section-label {
    font-size: 7pt;
    color: #b7794d;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-weight: bold;
    margin-bottom: 6px;
}

.section-title {
    font-size: 14pt;
    font-weight: bold;
    color: #2d2620;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e8d8c4;
}

/* ─── Advice bullets ─── */
.advice-item {
    padding: 11px 14px 11px 42px;
    margin-bottom: 8px;
    background: #fffaf5;
    border: 1px solid #e8d8c4;
    border-left: 4px solid #b7794d;
    border-radius: 0 6px 6px 0;
    font-size: 10pt;
    color: #2d2620;
    line-height: 1.55;
    position: relative;
}

.advice-bullet {
    position: absolute;
    left: 14px;
    top: 12px;
    color: #b7794d;
    font-weight: bold;
    font-size: 13pt;
    line-height: 1;
}

/* ─── Palette ─── */
.palette-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 6px 0;
}

.palette-table td {
    width: 20%;
    vertical-align: top;
    text-align: center;
    padding: 0 3px;
}

.swatch {
    height: 72px;
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.08);
    margin-bottom: 7px;
}

.swatch-name {
    font-size: 8.5pt;
    font-weight: bold;
    color: #2d2620;
    margin-bottom: 2px;
    line-height: 1.3;
}

.swatch-hex {
    font-size: 7.5pt;
    color: #8a7264;
}

/* ─── Materials: card blocks ─── */
.material-card {
    border: 1px solid #e8d8c4;
    border-radius: 8px;
    margin-bottom: 12px;
    overflow: hidden;
}

.material-header {
    background: #f0e3d4;
    padding: 9px 16px;
    font-size: 10pt;
    font-weight: bold;
    color: #6b4225;
    border-bottom: 1px solid #e8d8c4;
}

.material-body {
    padding: 12px 16px;
}

.material-recs {
    margin-bottom: 10px;
}

.material-recs-label {
    font-size: 7.5pt;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #8a7264;
    font-weight: bold;
    margin-bottom: 5px;
}

.rec-item {
    font-size: 9.5pt;
    color: #2d2620;
    padding: 2px 0 2px 14px;
    position: relative;
}

.rec-item::before {
    content: "→";
    position: absolute;
    left: 0;
    color: #b7794d;
    font-size: 9pt;
}

.do-dont-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 2px;
}

.do-dont-table td {
    width: 50%;
    padding: 8px 10px;
    vertical-align: top;
    font-size: 9pt;
}

.do-col {
    background: #edf5ec;
    border-radius: 4px 0 0 4px;
    border: 1px solid #c8dfc5;
}

.dont-col {
    background: #fcecea;
    border-radius: 0 4px 4px 0;
    border: 1px solid #f0c8c4;
    border-left: none;
}

.do-dont-label {
    font-size: 7pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 5px;
}

.do-label { color: #3d6b35; }
.dont-label { color: #8b2e24; }

.do-item {
    color: #3d6b35;
    font-size: 9pt;
    padding: 2px 0;
}

.dont-item {
    color: #8b2e24;
    font-size: 9pt;
    padding: 2px 0;
}

/* ─── Layout tips ─── */
.tip-item {
    display: block;
    margin-bottom: 9px;
    padding: 11px 14px 11px 50px;
    background: #fffaf5;
    border: 1px solid #e8d8c4;
    border-radius: 6px;
    font-size: 10pt;
    color: #2d2620;
    line-height: 1.55;
    position: relative;
}

.tip-number-badge {
    position: absolute;
    left: 12px;
    top: 50%;
    margin-top: -12px;
    width: 24px;
    height: 24px;
    background: #b7794d;
    border-radius: 50%;
    text-align: center;
    line-height: 24px;
    font-size: 8.5pt;
    font-weight: bold;
    color: #ffffff;
}

/* ─── Product ideas: compact text list ─── */
.product-row {
    border-bottom: 1px solid #e8d8c4;
    padding: 9px 0;
}

.product-row:last-child {
    border-bottom: none;
}

.product-row-title {
    font-size: 9.5pt;
    font-weight: bold;
    color: #6b4225;
    margin-bottom: 3px;
}

.product-row-meta {
    font-size: 9pt;
    color: #2d2620;
    line-height: 1.5;
}

.product-field {
    margin-bottom: 5px;
}

.product-field-label {
    font-size: 7pt;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #8a7264;
    font-weight: bold;
    margin-bottom: 2px;
}

.product-field-value {
    font-size: 9.5pt;
    color: #2d2620;
}

/* ─── Image pages ─── */
.image-page {
    padding: 36px 44px;
}

.image-block {
    margin-bottom: 32px;
}

.image-title {
    font-size: 14pt;
    font-weight: bold;
    color: #2d2620;
    margin-bottom: 4px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e8d8c4;
}

.image-subtitle {
    font-size: 9pt;
    color: #8a7264;
    font-style: italic;
    margin-bottom: 14px;
}

.advice-image {
    width: 100%;
    max-height: 300px;
    border-radius: 8px;
    border: 1px solid #e8d8c4;
    display: block;
}

.image-caption {
    margin-top: 9px;
    font-size: 8.5pt;
    color: #8a7264;
    font-style: italic;
    text-align: center;
    line-height: 1.4;
}

/* ─── Closing block ─── */
.closing {
    background: #f5ede2;
    border: 1px solid #ddd0c0;
    border-radius: 8px;
    padding: 22px 26px;
    margin-top: 8px;
    text-align: center;
}

.closing-title {
    font-size: 12pt;
    font-weight: bold;
    color: #2d2620;
    margin-bottom: 8px;
}

.closing-text {
    font-size: 9.5pt;
    color: #5a4638;
    line-height: 1.6;
    margin-bottom: 10px;
}

.closing-cta {
    font-size: 9pt;
    color: #b7794d;
    font-weight: bold;
}

/* ─── Footer ─── */
.footer {
    border-top: 1px solid #e8d8c4;
    margin-top: 28px;
    padding-top: 10px;
    text-align: center;
    font-size: 7.5pt;
    color: #a0897a;
    line-height: 1.6;
}

/* ─── Page breaks ─── */
.page-break { page-break-after: always; }
.avoid-break { page-break-inside: avoid; }
</style>
</head>
<body>
<div class="page">

    {{-- ══ COVER / HEADER ══ --}}
    <div class="cover">
        <div class="brand-label">Boer Staphorst &middot; Interieuradvies</div>
        <div class="cover-title">Jouw Persoonlijk<br>Interieuradvies</div>
        <div class="cover-tagline">Samengesteld op basis van jouw stijl, sfeer en woonwensen.</div>

        @if($submission->name)
        <div class="cover-name">Opgesteld voor <strong>{{ $submission->name }}</strong></div>
        @endif

        {{-- Meta summary --}}
        <table class="meta-row" cellspacing="0" cellpadding="0">
            <tr>
                <td style="padding-right:6px; padding-left:0;">
                    <div class="meta-cell">
                        <div class="meta-label">Woonstijl</div>
                        <div class="meta-value">{{ $submission->style }}</div>
                    </div>
                </td>
                @if($submission->mood_words)
                <td style="padding-right:6px;">
                    <div class="meta-cell">
                        <div class="meta-label">Sfeer</div>
                        <div class="meta-value">{{ $submission->mood_words }}</div>
                    </div>
                </td>
                @endif
                @if($submission->colors)
                <td style="padding-right:6px;">
                    <div class="meta-cell">
                        <div class="meta-label">Kleuren</div>
                        <div class="meta-value">{{ $submission->colors }}</div>
                    </div>
                </td>
                @endif
                <td style="padding-right:0;">
                    <div class="meta-cell">
                        <div class="meta-label">Datum</div>
                        <div class="meta-value">{{ $submission->created_at->format('d M Y') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">

        {{-- ══ ADVIES ══ --}}
        @if(!empty($submission->advice_bullets))
        <div class="section avoid-break">
            <div class="section-label">Persoonlijk advies</div>
            <div class="section-title">Stijladvies voor jouw woonkamer</div>
            @foreach($submission->advice_bullets as $bullet)
            <div class="advice-item avoid-break">
                <span class="advice-bullet">&#8226;</span>
                {{ $bullet }}
            </div>
            @endforeach
        </div>
        @endif

        {{-- ══ KLEURENPALET ══ --}}
        @if(!empty($submission->palette))
        <div class="section avoid-break">
            <div class="section-label">Kleuradvies</div>
            <div class="section-title">Kleurenpalet</div>
            <table class="palette-table" cellspacing="0" cellpadding="0">
                <tr>
                    @foreach($submission->palette as $color)
                    <td>
                        <div class="swatch" style="background: {{ $color['hex'] ?? '#cccccc' }};"></div>
                        <div class="swatch-name">{{ $color['name'] ?? '' }}</div>
                        <div class="swatch-hex">{{ $color['hex'] ?? '' }}</div>
                    </td>
                    @endforeach
                </tr>
            </table>
        </div>
        @endif

        {{-- ══ MATERIALEN ══ --}}
        @if(!empty($submission->materials))
        <div class="section">
            <div class="section-label">Materiaaladvies</div>
            <div class="section-title">Materialen &amp; Afwerking</div>
            @foreach($submission->materials as $material)
            <div class="material-card avoid-break">
                <div class="material-header">{{ $material['category'] ?? '' }}</div>
                <div class="material-body">
                    @if(!empty($material['recommendations']))
                    <div class="material-recs">
                        <div class="material-recs-label">Aanbevelingen</div>
                        @foreach($material['recommendations'] as $rec)
                        <div class="rec-item">{{ $rec }}</div>
                        @endforeach
                    </div>
                    @endif
                    @if(!empty($material['do']) || !empty($material['dont']))
                    <table class="do-dont-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="do-col">
                                <div class="do-dont-label do-label">&#10003; Doen</div>
                                @foreach(($material['do'] ?? []) as $do)
                                <div class="do-item">{{ $do }}</div>
                                @endforeach
                            </td>
                            <td class="dont-col">
                                <div class="do-dont-label dont-label">&#10005; Vermijden</div>
                                @foreach(($material['dont'] ?? []) as $dont)
                                <div class="dont-item">{{ $dont }}</div>
                                @endforeach
                            </td>
                        </tr>
                    </table>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- ══ INDELINGSTIPS ══ --}}
        @if(!empty($submission->layout_tips))
        <div class="section avoid-break">
            <div class="section-label">Praktisch advies</div>
            <div class="section-title">Indelingstips</div>
            @foreach($submission->layout_tips as $index => $tip)
            <div class="tip-item avoid-break">
                <span class="tip-number-badge">{{ $index + 1 }}</span>
                {{ $tip }}
            </div>
            @endforeach
        </div>
        @endif

        {{-- ══ PRODUCTIDEEËN ══ --}}
        @if(!empty($submission->product_ideas))
        <div class="section avoid-break">
            <div class="section-label">Inspiratie</div>
            <div class="section-title">Productidee&euml;n</div>
            <div style="border: 1px solid #e8d8c4; border-radius: 8px; overflow: hidden; background: #fffaf5;">
                @foreach($submission->product_ideas as $product)
                <div class="product-row" style="padding: 9px 14px;">
                    <div class="product-row-title">{{ $product['category'] ?? '' }}</div>
                    <div class="product-row-meta">
                        @if(!empty($product['exampleSpecs'])){{ $product['exampleSpecs'] }}@endif
                        @if(!empty($product['material'])) &nbsp;&middot;&nbsp; <em>{{ $product['material'] }}</em>@endif
                        @if(!empty($product['colorHint'])) &nbsp;&middot;&nbsp; {{ $product['colorHint'] }}@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>{{-- end .content --}}

    {{-- ══ IMAGES PAGE ══ --}}
    @if(!empty($moodboardBase64) || !empty($inspirationBase64))
    <div class="page-break"></div>
    <div class="image-page">

        @if(!empty($moodboardBase64))
        <div class="image-block avoid-break">
            <div class="section-label">Visuele inspiratie</div>
            <div class="image-title">Moodboard</div>
            <div class="image-subtitle">Kleuren, materialen en stijlcombinaties die passen bij jouw woonwens.</div>
            <img src="{{ $moodboardBase64 }}" class="advice-image" alt="Moodboard" />
        </div>
        @endif

        @if(!empty($inspirationBase64))
        <div class="image-block avoid-break" style="margin-top:28px;">
            <div class="section-label">Ruimte-inspiratie</div>
            <div class="image-title">Jouw Ruimte in Deze Stijl</div>
            <div class="image-subtitle">Een beeld van hoe jouw woonkamer kan aanvoelen in de gekozen stijl.</div>
            <img src="{{ $inspirationBase64 }}" class="advice-image" alt="Ruimte-inspiratie" />
        </div>
        @endif

        {{-- ── Closing block ── --}}
        <div class="closing" style="margin-top:32px;">
            <div class="closing-title">Klaar om dit te realiseren?</div>
            <div class="closing-text">
                Dit advies is persoonlijk samengesteld op basis van jouw keuzes en woonwensen.<br>
                Gebruik het als leidraad bij het inrichten van jouw ruimte.
            </div>
            <div class="closing-cta">Bezoek Boer Staphorst voor professioneel interieuradvies op maat.</div>
        </div>

        <div class="footer">
            <strong style="color:#b7794d;">Boer Staphorst Interieuradvies</strong>
            &nbsp;&middot;&nbsp; Persoonlijk samengesteld &nbsp;&middot;&nbsp;
            {{ now()->format('d M Y') }}
        </div>

    </div>
    @else
    <div class="content" style="padding-top:0;">
        <div class="closing">
            <div class="closing-title">Klaar om dit te realiseren?</div>
            <div class="closing-text">
                Dit advies is persoonlijk samengesteld op basis van jouw keuzes en woonwensen.<br>
                Gebruik het als leidraad bij het inrichten van jouw ruimte.
            </div>
            <div class="closing-cta">Bezoek Boer Staphorst voor professioneel interieuradvies op maat.</div>
        </div>
        <div class="footer">
            <strong style="color:#b7794d;">Boer Staphorst Interieuradvies</strong>
            &nbsp;&middot;&nbsp; Persoonlijk samengesteld &nbsp;&middot;&nbsp;
            {{ now()->format('d M Y') }}
        </div>
    </div>
    @endif

</div>
</body>
</html>
