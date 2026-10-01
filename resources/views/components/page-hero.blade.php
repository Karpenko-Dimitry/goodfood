@props(['title', 'subtitle' => null, 'image' => null])
<section class="relative overflow-hidden bg-brand-dark py-20 text-white sm:py-28">
    @if ($image)
        <img src="{{ $image }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30">
    @endif
    <div class="absolute inset-0 bg-gradient-to-r from-brand-dark/90 to-brand/60"></div>
    <div class="container-x relative text-center">
        <h1 class="text-4xl sm:text-6xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mx-auto mt-4 max-w-2xl text-white/80">{{ $subtitle }}</p>
        @endif
        <nav class="mt-6 text-sm text-white/70">
            <a href="{{ route('home') }}" class="hover:text-white">{{ __('site.nav.home') }}</a>
            <span class="mx-2">/</span>
            <span class="text-white">{{ $title }}</span>
        </nav>
    </div>
</section>
