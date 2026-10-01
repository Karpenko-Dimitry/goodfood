@extends('layouts.app')

@section('title', $diet->name)
@section('description', $diet->excerpt)

@section('content')
    <x-page-hero :title="$diet->name" :subtitle="$diet->excerpt" :image="$diet->imageUrl()" />

    <section class="section">
        <div class="container-x grid gap-12 lg:grid-cols-[1fr_360px]">
            <div>
                <div class="prose-diet">{!! $diet->description !!}</div>

                <div class="mt-10 grid gap-6 sm:grid-cols-2">
                    <div class="bg-brand-light p-8">
                        <h3 class="mb-4 text-2xl">{{ __('site.diet.pros') }}</h3>
                        <ul class="space-y-2 text-sm">
                            @foreach ($diet->lines('pros') as $item)
                                <li class="flex gap-3"><span class="text-brand">✔</span>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="bg-panel p-8">
                        <h3 class="mb-4 text-2xl">{{ __('site.diet.cons') }}</h3>
                        <ul class="space-y-2 text-sm">
                            @foreach ($diet->lines('cons') as $item)
                                <li class="flex gap-3"><span class="text-red-600">✕</span>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="border border-line p-8">
                        <h3 class="mb-4 text-2xl">{{ __('site.diet.allowed') }}</h3>
                        <ul class="space-y-2 text-sm text-muted">
                            @foreach ($diet->lines('allowed_foods') as $item)
                                <li class="flex gap-3"><span class="text-brand">●</span>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="border border-line p-8">
                        <h3 class="mb-4 text-2xl">{{ __('site.diet.forbidden') }}</h3>
                        <ul class="space-y-2 text-sm text-muted">
                            @foreach ($diet->lines('forbidden_foods') as $item)
                                <li class="flex gap-3"><span class="text-red-600">●</span>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
                <div class="bg-panel p-8">
                    <h3 class="card-title">{{ __('site.diet.at_a_glance') }}</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-muted">{{ __('site.diet.difficulty') }}</dt><dd>{{ __('site.diet.level_'.$diet->difficulty) }}</dd></div>
                        @if ($diet->calories_min)
                            <div class="flex justify-between"><dt class="text-muted">{{ __('site.diet.calories') }}</dt><dd>{{ $diet->calories_min }}–{{ $diet->calories_max }} {{ __('site.macros.kcal') }}</dd></div>
                        @endif
                        @if ($diet->duration_days)
                            <div class="flex justify-between"><dt class="text-muted">{{ __('site.diet.duration') }}</dt><dd>{{ $diet->duration_days }} {{ __('site.diet.days') }}</dd></div>
                        @endif
                    </dl>
                    @if ($diet->protein_pct)
                        <div class="mt-6">
                            <x-macro-bar :protein="$diet->protein_pct / 4" :fat="$diet->fat_pct / 9" :carbs="$diet->carbs_pct / 4" class="!h-3" />
                            <div class="mt-3 flex justify-between text-xs">
                                <span><i class="mr-1 inline-block h-2 w-2 bg-brand"></i>{{ __('site.macros.protein') }} {{ $diet->protein_pct }}%</span>
                                <span><i class="mr-1 inline-block h-2 w-2 bg-amber-400"></i>{{ __('site.macros.fat') }} {{ $diet->fat_pct }}%</span>
                                <span><i class="mr-1 inline-block h-2 w-2 bg-sky-400"></i>{{ __('site.macros.carbs') }} {{ $diet->carbs_pct }}%</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="bg-brand p-8 text-white">
                    <h3 class="text-2xl">{{ __('site.diet.ai_box_title') }}</h3>
                    <p class="mt-3 text-sm text-white/80">{{ __('site.diet.ai_box_text') }}</p>
                    <a href="{{ route('plan.create', ['diet' => $diet->id]) }}" class="mt-6 inline-flex bg-white px-6 py-3 text-sm font-medium uppercase text-brand hover:bg-brand-light">{{ __('site.cta.get_plan') }}</a>
                </div>
            </aside>
        </div>
    </section>

    @if ($dishes->isNotEmpty())
        <section class="section bg-panel/50 pt-0">
            <div class="container-x">
                <h2 class="section-title">{!! __('site.diet.recipes_title') !!}</h2>
                <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($dishes as $dish)
                        <x-dish-card :dish="$dish" />
                    @endforeach
                </div>
                <div class="mt-10 text-center">
                    <a href="{{ route('dishes.index', ['diet' => $diet->slug]) }}" class="btn-ghost">{{ __('site.home.all_recipes') }}</a>
                </div>
            </div>
        </section>
    @endif

    @if ($others->isNotEmpty())
        <section class="section">
            <div class="container-x">
                <h2 class="section-title">{!! __('site.diet.others_title') !!}</h2>
                <div class="mt-12 grid gap-8 md:grid-cols-3">
                    @foreach ($others as $other)
                        <x-diet-card :diet="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
