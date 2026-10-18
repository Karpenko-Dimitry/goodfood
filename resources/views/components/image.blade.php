@props([
    'model' => null,       // Dish / Diet (HasImage): FileService presets + srcset
    'preset' => 'md',      // preset used for src and width/height (see App\FileConfigs)
    'src' => null,         // or a plain URL; Unsplash URLs get a srcset automatically
    'sizes' => '100vw',    // rendered width of the image, so the browser picks the right srcset entry
    'alt' => '',
    'priority' => false,   // the LCP image: load eagerly with high priority
    'responsive' => true,  // false for small fixed-size thumbnails
])
@php
    $srcset = null;
    $width = $height = null;

    if ($model) {
        $src = $model->imageUrl($preset);
        $srcset = $responsive ? $model->imageSrcset() : null;
        [$width, $height] = $model->imageDimensions($preset);
    } elseif ($src && $responsive) {
        $srcset = \App\Support\ExternalImage::srcset($src, [640, 960, 1280, 1920]);
        $src = \App\Support\ExternalImage::resized($src, 1280);
    }
@endphp
@if ($src)
    <img src="{{ $src }}"
         @if ($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
         @if ($width && $height) width="{{ $width }}" height="{{ $height }}" @endif
         alt="{{ $alt }}"
         @if ($priority) loading="eager" fetchpriority="high" @else loading="lazy" @endif
         decoding="async"
         {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-brand-light text-brand']) }}>
        <svg viewBox="0 0 24 24" class="h-12 w-12 opacity-60" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 21c0-6 1.5-10 7-13-.5 6-3 10-7 13Zm0 0c0-4.5-1.5-7.5-6-10 .5 4.5 2.5 7.5 6 10ZM9 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
    </div>
@endif
