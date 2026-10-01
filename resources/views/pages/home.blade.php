@extends('layouts.app')

@section('content')
    {{-- Hero --}}
    <section class="relative flex min-h-[640px] items-center overflow-hidden bg-brand-dark">
        <img src="https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=1920&q=80&auto=format&fit=crop" alt="" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/45 to-transparent"></div>
        <div class="container-x relative py-24 text-white">
            <p class="eyebrow !text-white/80">{{ __('site.home.hero_eyebrow') }}</p>
            <h1 class="mt-4 max-w-2xl text-5xl leading-[1.1] sm:text-7xl">{{ __('site.home.hero_title') }}</h1>
            <p class="mt-6 max-w-xl text-lg text-white/80">{{ __('site.home.hero_text') }}</p>
            <div class="mt-10 flex flex-wrap gap-4">
                <a href="{{ route('plan.create') }}" class="btn-primary">✨ {{ __('site.cta.get_plan') }}</a>
                <a href="{{ route('diets.index') }}" class="btn-outline">{{ __('site.home.browse_diets') }}</a>
            </div>
            <dl class="mt-16 flex flex-wrap gap-x-12 gap-y-6">
                <div><dt class="text-sm text-white/70">{{ __('site.home.stat_diets') }}</dt><dd class="font-serif text-4xl">{{ $stats['diets'] }}+</dd></div>
                <div><dt class="text-sm text-white/70">{{ __('site.home.stat_recipes') }}</dt><dd class="font-serif text-4xl">{{ $stats['dishes'] }}+</dd></div>
                <div><dt class="text-sm text-white/70">{{ __('site.home.stat_ai') }}</dt><dd class="font-serif text-4xl">24/7</dd></div>
            </dl>
        </div>
    </section>

    {{-- Program tabs (template: "Health Coaching Program") --}}
    @if ($featuredDiets->isNotEmpty())
        <section class="section">
            <div class="container-x">
                <h2 class="section-title reveal">{!! __('site.home.program_title') !!}</h2>
                <div class="mt-14 grid gap-8 lg:grid-cols-[320px_1fr]" data-tabs>
                    <div class="flex gap-2 overflow-x-auto lg:flex-col lg:overflow-visible" role="tablist">
                        @foreach ($featuredDiets as $diet)
                            <button role="tab" data-tab="{{ $loop->index }}"
                                    class="shrink-0 border-l-4 px-6 py-4 text-left font-serif text-xl transition {{ $loop->first ? 'border-brand bg-brand text-white' : 'border-transparent bg-panel hover:border-brand' }}">
                                {{ $diet->name }}
                            </button>
                        @endforeach
                    </div>
                    @foreach ($featuredDiets as $diet)
                        <div data-panel="{{ $loop->index }}" class="{{ $loop->first ? '' : 'hidden' }} grid min-w-0 items-center gap-10 md:grid-cols-2">
                            <x-image :src="$diet->imageUrl()" :alt="$diet->name" class="aspect-[4/3] w-full" />
                            <div>
                                <h3 class="text-3xl sm:text-4xl">{{ $diet->name }}</h3>
                                <p class="mt-5 leading-relaxed text-muted">{{ $diet->excerpt }}</p>
                                <ul class="mt-6 space-y-2">
                                    @foreach (array_slice($diet->lines('pros'), 0, 3) as $pro)
                                        <li class="flex gap-3 text-sm"><span class="text-brand">✔</span>{{ $pro }}</li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('diets.show', $diet) }}" class="btn-primary mt-8">{{ __('site.home.find_out_more') }}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Services grid (template: "Health Coaching Services") --}}
    <section class="section bg-white pt-0">
        <div class="container-x">
            <h2 class="section-title reveal">{!! __('site.home.collection_title') !!}</h2>
            <p class="reveal mx-auto mt-4 max-w-2xl text-center text-muted">{{ __('site.home.collection_text') }}</p>
            <div class="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($diets as $diet)
                    <x-diet-card :diet="$diet" />
                @endforeach
            </div>
            <div class="mt-12 text-center">
                <a href="{{ route('diets.index') }}" class="btn-ghost">{{ __('site.home.all_diets') }}</a>
            </div>
        </div>
    </section>

    {{-- AI CTA --}}
    <section class="relative overflow-hidden bg-brand py-20 text-white">
        <div class="absolute -right-20 -top-20 h-80 w-80 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-white/5"></div>
        <div class="container-x relative grid items-center gap-12 lg:grid-cols-2">
            <div class="reveal">
                <p class="eyebrow !text-white/70">OpenAI · Laravel AI SDK</p>
                <h2 class="mt-3 text-4xl sm:text-5xl">{{ __('site.home.ai_title') }}</h2>
                <p class="mt-5 text-white/80">{{ __('site.home.ai_text') }}</p>
                <a href="{{ route('plan.create') }}" class="mt-8 inline-flex bg-white px-7 py-3.5 text-sm font-medium uppercase tracking-wide text-brand hover:bg-brand-light">{{ __('site.cta.get_plan') }} →</a>
            </div>
            <ol class="reveal grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                @foreach (['step1', 'step2', 'step3'] as $i => $step)
                    <li class="flex items-start gap-5 bg-white/10 p-6 backdrop-blur">
                        <span class="font-serif text-4xl text-white/60">0{{ $i + 1 }}</span>
                        <div>
                            <h3 class="text-xl">{{ __("site.home.{$step}_title") }}</h3>
                            <p class="mt-1 text-sm text-white/75">{{ __("site.home.{$step}_text") }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Recipes (template: "Completed Projects") --}}
    <section class="section">
        <div class="container-x">
            <h2 class="section-title reveal">{!! __('site.home.recipes_title') !!}</h2>
            <p class="reveal mx-auto mt-4 max-w-2xl text-center text-muted">{{ __('site.home.recipes_text') }}</p>
            <div class="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($dishes as $dish)
                    <x-dish-card :dish="$dish" />
                @endforeach
            </div>
            <div class="mt-12 text-center">
                <a href="{{ route('dishes.index') }}" class="btn-ghost">{{ __('site.home.all_recipes') }}</a>
            </div>
        </div>
    </section>
@endsection

