<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
        | Vaste-slot- en antwoordoptie-/materiaalfoto's van de quiz (zie QuizImageManifest).
        | Dit beschrijft expliciet het gedrag van vóór deze disk bestond: rechtstreeks in
        | public/ geschreven bestanden, direct door de webserver bediend. Lokaal (sqlite, geen
        | AWS-sleutels) blijft dit de standaard — zie QUIZ_IMAGES_DISK hieronder en
        | QuizImageManifest::disk(). Op Laravel Cloud zet je QUIZ_IMAGES_DISK op "s3" zodra er
        | Object Storage aan de omgeving hangt, zodat uploads een deploy overleven.
        |
        | Geen 'url' hier: QuizImageManifest bouwt voor deze disk bewust een root-relatief pad
        | i.p.v. een op APP_URL gebaseerde absolute URL — APP_URL komt lokaal (en soms ook op een
        | los geconfigureerde omgeving) niet per se overeen met het adres waarop de site
        | daadwerkelijk bereikbaar is, een root-relatief pad lost zichzelf altijd correct op t.o.v.
        | de aanvragende pagina. Zie QuizImageManifest::buildUrl().
        */
        'quiz_images' => [
            'driver' => 'local',
            'root' => public_path(),
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Quiz-afbeeldingen disk
    |--------------------------------------------------------------------------
    |
    | Welke disk hierboven QuizImageManifest gebruikt voor antwoordoptie-/materiaal-/sfeer-/
    | start-/overgangsschermfoto's. Default "quiz_images" (lokaal, huidig gedrag); op Laravel
    | Cloud na het koppelen van Object Storage zet je dit op "s3".
    |
    */

    'quiz_images_disk' => env('QUIZ_IMAGES_DISK', 'quiz_images'),

    /*
    |--------------------------------------------------------------------------
    | Quiz-PDF's disk
    |--------------------------------------------------------------------------
    |
    | Welke disk de gegenereerde quizresultaat-PDF's gebruikt (zie QuizResultPdfService/
    | SubmissionPdfController). Bewust een aparte instelling van quiz_images_disk, met "public"
    | (Laravel's eigen, altijd-aanwezige disk) als default i.p.v. "quiz_images": bestaande
    | inzendingen hebben hun PDF al onder die disk staan (storage/app/public/submissions/...) —
    | zou dit op "quiz_images" default staan, dan zou de app na deze wijziging op een heel andere
    | fysieke plek zoeken en oudere PDF's niet meer terugvinden. Blijft dit op "public" staan, dan
    | verandert er voor bestaande én nieuwe PDF's niets. `quiz:migrate-images` kopieert alleen
    | quiz-afbeeldingen, geen PDF's — zet dit pas op "s3" als daar apart voor gekozen wordt, mét
    | een vergelijkbare eenmalige kopieerstap voor de bestaande PDF's uit storage/app/public.
    |
    */

    'quiz_pdfs_disk' => env('QUIZ_PDFS_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
