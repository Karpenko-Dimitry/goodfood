@extends('layouts.app')

@section('title', __('site.nav.recipes'))

@section('content')
    <x-page-hero :title="__('site.nav.recipes')" :subtitle="__('site.recipes.subtitle')"
                 image="https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=1600&q=70&auto=format&fit=crop" />

    <section class="section">
        <div class="container-x">
            {{-- Category pills --}}
            <div class="mb-8 flex flex-wrap justify-center gap-2">
                <a href="{{ route('dishes.index', request()->except('category', 'page')) }}"
                   class="px-5 py-2 text-sm uppercase tracking-wide {{ request('category') ? 'bg-panel hover:bg-brand-light' : 'bg-brand text-white' }}">{{ __('site.recipes.all') }}</a>
                @foreach ($categories as $category)
                    <a href="{{ route('dishes.index', array_merge(request()->except('page'), ['category' => $category->slug])) }}"
                       class="px-5 py-2 text-sm uppercase tracking-wide {{ request('category') === $category->slug ? 'bg-brand text-white' : 'bg-panel hover:bg-brand-light' }}">{{ $category->name }}</a>
                @endforeach
            </div>

            {{-- Filters --}}
            <form method="get" class="mb-12 grid gap-4 bg-panel p-6 sm:grid-cols-2 lg:grid-cols-5">
                @if (request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="lg:col-span-2">
                    <label class="label" for="q">{{ __('site.recipes.search') }}</label>
                    <input id="q" name="q" value="{{ request('q') }}" class="field" placeholder="{{ __('site.recipes.search_placeholder') }}">
                </div>
                <div>
                    <label class="label" for="diet">{{ __('site.recipes.diet') }}</label>
                    <select id="diet" name="diet" class="field">
                        <option value="">—</option>
                        @foreach ($diets as $diet)
                            <option value="{{ $diet->slug }}" @selected(request('diet') === $diet->slug)>{{ $diet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="max_kcal">{{ __('site.recipes.max_kcal') }}</label>
                    <select id="max_kcal" name="max_kcal" class="field">
                        <option value="">—</option>
                        @foreach ([300, 400, 500, 600] as $kcal)
                            <option value="{{ $kcal }}" @selected(request('max_kcal') == $kcal)>≤ {{ $kcal }} {{ __('site.macros.kcal') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <select name="sort" class="field" aria-label="{{ __('site.recipes.sort') }}">
                        <option value="">{{ __('site.recipes.sort_kcal') }}</option>
                        <option value="protein" @selected(request('sort') === 'protein')>{{ __('site.recipes.sort_protein') }}</option>
                    </select>
                    <button class="btn-primary !px-5">{{ __('site.recipes.apply') }}</button>
                </div>
            </form>

            @if ($dishes->isEmpty())
                <p class="py-16 text-center text-muted">{{ __('site.recipes.empty') }}</p>
            @else
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($dishes as $dish)
                        <x-dish-card :dish="$dish" />
                    @endforeach
                </div>
                <div class="mt-12">{{ $dishes->links() }}</div>
            @endif
        </div>
    </section>
@endsection
