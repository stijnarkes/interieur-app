<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8" />
<title>Jouw woonstijl</title>
<style>
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

.cover-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
}

.cover-secondary {
    margin-top: 14px;
    font-size: 9pt;
    color: #6b4225;
}

.cover-secondary strong {
    color: #2d2620;
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

.photo-grid {
    width: 100%;
}

.photo-item {
    display: inline-block;
    width: 22%;
    margin: 0 3% 12px 0;
    text-align: center;
    vertical-align: top;
}

.photo-item img {
    width: 100%;
    height: 70px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 6px;
}

.photo-item-placeholder {
    width: 100%;
    height: 70px;
    background: #f0e3d4;
    border-radius: 8px;
    margin-bottom: 6px;
}

.materials-board-image {
    width: 100%;
    max-height: 220px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 12px;
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
</style>
</head>
<body>

@php
    $primaryStyle = $result['primaryStyle'] ?? null;

    // dompdf negeert CSS object-fit volledig — een <img> met een vaste breedte én hoogte wordt
    // dus altijd platgedrukt/uitgerekt i.p.v. bijgesneden, zoals een browser wel zou doen. Voor
    // tegels met zo'n vaste verhouding (het moodboard) knippen we de foto daarom hier zelf met GD
    // tot de doelverhouding, vóórdat 'ie als base64 in de PDF komt.
    $cropToCoverRatio = function (string $contents, float $targetRatio): string {
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return $contents;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $currentRatio = $width / $height;

        if ($currentRatio > $targetRatio) {
            $cropWidth = (int) round($height * $targetRatio);
            $cropHeight = $height;
            $srcX = (int) round(($width - $cropWidth) / 2);
            $srcY = 0;
        } else {
            $cropWidth = $width;
            $cropHeight = (int) round($width / $targetRatio);
            $srcX = 0;
            $srcY = (int) round(($height - $cropHeight) / 2);
        }

        $cropped = imagecreatetruecolor($cropWidth, $cropHeight);
        imagecopy($cropped, $image, 0, 0, $srcX, $srcY, $cropWidth, $cropHeight);
        imagedestroy($image);

        ob_start();
        imagewebp($cropped, null, 85);
        $webp = ob_get_clean();
        imagedestroy($cropped);

        return $webp;
    };

    // Embedt de foto als base64 data-URI i.p.v. een lokaal bestandspad aan dompdf te geven: dat
    // laatste bestaat niet meer zodra QUIZ_IMAGES_DISK op S3 staat. contentsFor() snapt zowel het
    // oude root-relatieve pad (oudere inzendingen) als een volledige URL (zie
    // QuizConfigController), en leest hoe dan ook alleen van de eigen, geconfigureerde disk.
    // $coverRatio (breedte/hoogte) knipt de foto bij tot die verhouding — alleen nodig voor
    // tegels met een vaste hoogte in de layout (zie hierboven).
    $resolveImage = function (?string $path, ?float $coverRatio = null) use ($cropToCoverRatio) {
        if (! $path) {
            return null;
        }

        $contents = \App\Support\QuizImageManifest::contentsFor($path);

        if (! $contents) {
            return null;
        }

        if ($coverRatio !== null) {
            return 'data:image/webp;base64,'.base64_encode($cropToCoverRatio($contents, $coverRatio));
        }

        $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'image/webp',
        };

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    };
@endphp

@php $coverImage = $resolveImage($primaryStyle['heroImage'] ?? null); @endphp

<div class="cover">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top; {{ $coverImage ? 'width: 58%;' : '' }}">
                <div class="brand-label">Boer Staphorst &middot; Interieuradvies</div>
                <div class="cover-title">{{ $result['resultName'] ?? 'Jouw woonstijl' }}</div>
                @if (!empty($primaryStyle['subtitle']))
                <div class="cover-subtitle">{{ $primaryStyle['subtitle'] }}</div>
                @endif
                <div class="cover-description">{{ $primaryStyle['longDescription'] ?? ($result['description'] ?? '') }}</div>

                @if (!empty($result['secondaryStyleLabel']))
                <div class="cover-secondary">Past ook goed bij jou: <strong>{{ $result['secondaryStyleLabel'] }}</strong></div>
                @endif
            </td>
            @if ($coverImage)
            <td style="vertical-align: top; width: 42%; padding-left: 18px;">
                <img src="{{ $coverImage }}" class="cover-image" alt="" />
            </td>
            @endif
        </tr>
    </table>
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
    <div class="section-title">Kleuren ter inspiratie</div>
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
    <div class="section-title accent-colors-title">Kies zelf een accentkleur die bij je past</div>
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

@if (!empty($primaryStyle['materials']) || !empty($primaryStyle['materialsImage']))
@php $materialsImage = $resolveImage($primaryStyle['materialsImage'] ?? null); @endphp
<div class="section">
    <div class="section-title">Materialen die bij jou passen</div>
    @if ($materialsImage)
    <img src="{{ $materialsImage }}" class="materials-board-image" alt="Materialen die bij jouw stijl passen" />
    @endif
    @if (!empty($primaryStyle['materials']))
    <div class="pill-row">
        @foreach ($primaryStyle['materials'] as $materialName)
        <span class="pill">{{ is_array($materialName) ? ($materialName['name'] ?? '') : $materialName }}</span>
        @endforeach
    </div>
    @endif
    @if (!empty($primaryStyle['materialsTip']))
    <div class="tip-box">{{ $primaryStyle['materialsTip'] }}</div>
    @endif
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

@if (!empty($result['moodboard']))
<div class="section">
    <div class="section-title">Jouw persoonlijke moodboard</div>
    <div class="photo-grid">
        @foreach ($result['moodboard'] as $photo)
        {{-- Tegel is 22% breed × 70px hoog (zie .photo-item) — vaste verhouding, dus bijknippen i.p.v. uitrekken. --}}
        @php $photoImage = $resolveImage($photo['image'] ?? null, 2.2); @endphp
        <div class="photo-item">
            @if ($photoImage)
                <img src="{{ $photoImage }}" alt="{{ $photo['title'] ?? '' }}" />
            @else
                <div class="photo-item-placeholder"></div>
            @endif
        </div>
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
    Wil je jouw woonstijl vertalen naar jouw eigen woonkamer? Plan een interieuradvies bij Boer Staphorst via boer-staphorst.nl/wonen/interieuradvies.
</div>

</body>
</html>
