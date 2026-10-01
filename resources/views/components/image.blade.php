@props(['src' => null, 'alt' => ''])
@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-brand-light text-brand']) }}>
        <svg viewBox="0 0 24 24" class="h-12 w-12 opacity-60" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 21c0-6 1.5-10 7-13-.5 6-3 10-7 13Zm0 0c0-4.5-1.5-7.5-6-10 .5 4.5 2.5 7.5 6 10ZM9 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
    </div>
@endif
