{{--
    SEO / social / AI meta. Pages customize it with:
      @section('title'), @section('description'), @section('robots'), @section('og_type'), @section('og_image')
      @push('jsonld') <x-json-ld :data="..." /> @endpush
--}}
@php
    $siteName = config('app.name');
    $title = trim($__env->yieldContent('title'));
    $fullTitle = $title !== '' ? "{$title} — {$siteName}" : $siteName;
    $description = \Illuminate\Support\Str::limit(trim(strip_tags($__env->yieldContent('description', __('site.meta.description')))), 160);
    $page = (int) request('page');
    $canonical = url()->current().($page > 1 ? '?page='.$page : '');
    $ogImage = $__env->yieldContent('og_image', \App\Support\ExternalImage::resized('https://images.unsplash.com/photo-1490645935967-10de6ba17061', 1200).'&h=630');
    $ogLocales = ['uk' => 'uk_UA', 'ru' => 'ru_RU', 'en' => 'en_US'];
@endphp
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1')">
<link rel="canonical" href="{{ $canonical }}">
@foreach (config('app.locales') as $code => $label)
    <link rel="alternate" hreflang="{{ $code }}" href="{{ localized_url($code) }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ localized_url(config('app.locale')) }}">

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:title" content="{{ $title !== '' ? $title : $siteName }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $title !== '' ? $title : $siteName }}">
<meta property="og:locale" content="{{ $ogLocales[app()->getLocale()] ?? app()->getLocale() }}">
@foreach (array_diff_key($ogLocales, [app()->getLocale() => true]) as $ogLocale)
    <meta property="og:locale:alternate" content="{{ $ogLocale }}">
@endforeach
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title !== '' ? $title : $siteName }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">

<meta name="theme-color" content="#417500">
<meta name="application-name" content="{{ $siteName }}">
@if ($code = config('services.seo.google_verification'))
    <meta name="google-site-verification" content="{{ $code }}">
@endif
@if ($code = config('services.seo.bing_verification'))
    <meta name="msvalidate.01" content="{{ $code }}">
@endif
<link rel="alternate" type="text/plain" title="LLM-friendly site summary" href="{{ url('/llms.txt') }}">

<x-json-ld :data="\App\Support\StructuredData::site()" />
@stack('jsonld')
