<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&display=swap" rel="stylesheet" />
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
