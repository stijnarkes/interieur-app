@extends('layouts.app', ['viteEntries' => ['resources/js/partner.js']])

@section('title', 'Jullie gezamenlijke woonstijl')

@section('content')
@php($siteContent = \App\Models\SiteContent::current())
<main class="app-shell">
    <section
        class="card results report"
        id="partnerResultRoot"
        data-access-token="{{ $accessToken }}"
        data-cta-label="{{ $siteContent->email_cta_label }}"
        data-cta-url="{{ $siteContent->email_cta_url }}"
    >
        <div id="partnerResultMount"></div>
    </section>
</main>
@endsection
