{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($entries as [$route, $params, $lastmod, $changefreq, $priority, $images])
@foreach ($locales as $locale)
    <url>
        <loc>{{ route($route, $params + ['locale' => $locale]) }}</loc>
@foreach ($locales as $alternate)
        <xhtml:link rel="alternate" hreflang="{{ $alternate }}" href="{{ route($route, $params + ['locale' => $alternate]) }}"/>
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ route($route, $params + ['locale' => config('app.locale')]) }}"/>
@if ($lastmod)
        <lastmod>{{ $lastmod->toAtomString() }}</lastmod>
@endif
        <changefreq>{{ $changefreq }}</changefreq>
        <priority>{{ $priority }}</priority>
@foreach (array_filter($images) as $image)
        <image:image><image:loc>{{ $image }}</image:loc></image:image>
@endforeach
    </url>
@endforeach
@endforeach
</urlset>
