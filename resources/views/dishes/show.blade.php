@extends('layouts.app')

@section('title', $dish->name)
@section('description', $dish->excerpt)

@section('content')
    @php($split = $dish->macroSplit())

    <section class="bg-panel/60">
        <div class="container-x grid items-center gap-10 py-12 lg:grid-cols-2 lg:py-20">
            <div>
                <nav class="mb-6 text-sm text-muted">
                    <a href="{{ route('home') }}" class="hover:text-brand">{{ __('site.nav.home') }}</a> /
                    <a href="{{ route('dishes.index') }}" class="hover:text-brand">{{ __('site.nav.recipes') }}</a>
                    @if ($dish->category)
                        / <a href="{{ route('dishes.index', ['category' => $dish->category->slug]) }}" class="hover:text-brand">{{ $dish->category->name }}</a>
                    @endif
                </nav>
                @if ($dish->isAiGenerated())
                    <span class="chip mb-4">✨ {{ __('site.recipes.ai_badge') }}</span>
                @endif
                <h1 class="text-4xl leading-tight sm:text-6xl">{{ $dish->name }}</h1>
                <p class="mt-5 text-lg text-muted">{{ $dish->excerpt }}</p>

                <div class="mt-8 flex flex-wrap gap-6 text-sm">
                    @if ($dish->totalMinutes())
                        <span>⏱ <b>{{ $dish->totalMinutes() }}</b> {{ __('site.recipes.min') }}</span>
                    @endif
                    <span>🍽 <b>{{ $dish->servings }}</b> {{ __('site.recipes.servings') }}</span>
                    <span>🔥 <b>{{ $dish->calories }}</b> {{ __('site.macros.kcal') }} / {{ __('site.recipes.serving') }}</span>
                </div>

                @if ($dish->diets->isNotEmpty())
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($dish->diets as $diet)
                            <a href="{{ route('diets.show', $diet) }}" class="chip hover:bg-brand hover:text-white">{{ $diet->icon }} {{ $diet->name }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
            <x-image :src="$dish->imageUrl()" :alt="$dish->name" class="aspect-[4/3] w-full shadow-2xl" />
        </div>
    </section>

    <section class="section">
        <div class="container-x grid gap-12 lg:grid-cols-[360px_1fr]">
            <aside class="space-y-8 lg:sticky lg:top-28 lg:self-start">
                {{-- Nutrition facts --}}
                <div class="border-2 border-ink p-6">
                    <h2 class="border-b-8 border-ink pb-2 text-3xl">{{ __('site.recipes.nutrition') }}</h2>
                    <p class="border-b border-ink py-2 text-sm">{{ __('site.recipes.per_serving') }}</p>
                    <div class="flex items-end justify-between border-b-4 border-ink py-2">
                        <span class="text-lg font-bold">{{ __('site.macros.calories') }}</span>
                        <span class="text-4xl font-bold">{{ $dish->calories }}</span>
                    </div>
                    @foreach (['protein' => 'bg-brand', 'fat' => 'bg-amber-400', 'carbs' => 'bg-sky-400'] as $macro => $color)
                        <div class="border-b border-line py-3">
                            <div class="flex justify-between text-sm">
                                <span class="font-semibold">{{ __('site.macros.'.$macro) }}</span>
                                <span>{{ $dish->{$macro} }} {{ __('site.macros.g') }} · {{ $split[$macro] }}%</span>
                            </div>
                            <div class="mt-2 h-1.5 bg-line"><div class="{{ $color }} h-full" style="width: {{ $split[$macro] }}%"></div></div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-panel p-8">
                    <h2 class="card-title">{{ __('site.recipes.ingredients') }}</h2>
                    <ul class="space-y-3 text-sm">
                        @foreach ($dish->lines('ingredients') as $ingredient)
                            <li>
                                <label class="flex cursor-pointer gap-3">
                                    <input type="checkbox" class="peer mt-0.5 accent-brand">
                                    <span class="peer-checked:text-muted peer-checked:line-through">{{ $ingredient }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            <div>
                <h2 class="text-4xl">{{ __('site.recipes.instructions') }}</h2>
                <ol class="mt-8 space-y-6">
                    @foreach ($dish->lines('instructions') as $step)
                        <li class="flex gap-6">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center bg-brand font-serif text-xl text-white">{{ $loop->iteration }}</span>
                            <p class="pt-2.5 leading-relaxed text-muted">{{ $step }}</p>
                        </li>
                    @endforeach
                </ol>

                @if ($related->isNotEmpty())
                    <h2 class="mt-20 text-3xl">{{ __('site.recipes.related') }}</h2>
                    <div class="mt-8 grid gap-6 md:grid-cols-3">
                        @foreach ($related as $item)
                            <x-dish-card :dish="$item" />
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
