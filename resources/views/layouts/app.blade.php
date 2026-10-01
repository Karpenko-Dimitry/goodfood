<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', __('site.meta.description'))">
    @foreach (config('app.locales') as $code => $label)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ localized_url($code) }}">
    @endforeach
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#content" class="sr-only focus:not-sr-only">{{ __('site.skip') }}</a>

    @include('partials.header')

    <main id="content" class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')

    <button data-to-top class="print:hidden fixed bottom-6 right-6 z-40 flex h-10 w-10 items-center justify-center bg-brand text-white opacity-0 transition hover:bg-brand-dark" aria-label="Top">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5m-7 7 7-7 7 7"/></svg>
    </button>
</body>
</html>
