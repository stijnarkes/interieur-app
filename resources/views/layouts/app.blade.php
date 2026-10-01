<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/png" href="{{ asset('images/branding/boer-staphorst-favicon.png') }}" />
    {{-- Clarendon LT Std wordt zelf gehost via resources/fonts/clarendon (@font-face in app.css) —
         geen Google Fonts meer nodig sinds Fraunces hierdoor is vervangen. --}}
    {{-- De partnerpagina's (resources/views/partner/*.blade.php) laden hun eigen, kleinere
         entry (resources/js/partner.js) i.p.v. app.js — anders zou app.js's eigen boot() óók
         #quizRoot in de ingesloten quiz-app-partial oppikken en initQuiz() een tweede keer
         aanroepen, met dubbele event-listeners op dezelfde knoppen tot gevolg. --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(array_merge(['resources/css/app.css'], $viteEntries ?? ['resources/js/app.js']))
    @endif
    @stack('styles')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
