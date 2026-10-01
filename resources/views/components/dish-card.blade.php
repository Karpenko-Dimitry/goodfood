@props(['dish'])
<a href="{{ route('dishes.show', $dish) }}" class="group reveal flex flex-col bg-panel transition hover:-translate-y-1 hover:shadow-xl">
    <div class="relative aspect-[4/3] overflow-hidden">
        <x-image :src="$dish->imageUrl()" :alt="$dish->name" class="h-full w-full transition duration-500 group-hover:scale-105" />
        @if ($dish->category)
            <span class="absolute left-4 top-4 bg-brand px-3 py-1 text-xs uppercase tracking-wide text-white">{{ $dish->category->name }}</span>
        @endif
        <span class="absolute bottom-4 right-4 bg-white/95 px-3 py-1 text-sm font-medium">{{ $dish->calories }} {{ __('site.macros.kcal') }}</span>
    </div>
    <div class="flex flex-1 flex-col p-6">
        <h3 class="font-serif text-xl leading-snug group-hover:text-brand">{{ $dish->name }}</h3>
        <p class="mt-2 line-clamp-2 flex-1 text-sm text-muted">{{ $dish->excerpt }}</p>
        <x-macro-bar :protein="$dish->protein" :fat="$dish->fat" :carbs="$dish->carbs" class="mt-5" />
        <div class="mt-3 grid grid-cols-3 text-center text-xs text-muted">
            <span><b class="block text-sm text-ink">{{ $dish->protein }}</b>{{ __('site.macros.p') }}</span>
            <span><b class="block text-sm text-ink">{{ $dish->fat }}</b>{{ __('site.macros.f') }}</span>
            <span><b class="block text-sm text-ink">{{ $dish->carbs }}</b>{{ __('site.macros.c') }}</span>
        </div>
    </div>
</a>
