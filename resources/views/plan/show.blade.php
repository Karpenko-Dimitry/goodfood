@extends('layouts.app')

@section('title', __('site.plan.result_title'))

@section('content')
    <x-page-hero :title="__('site.plan.result_title')" :subtitle="$plan->name ? __('site.plan.for', ['name' => $plan->name]) : null" />

    <section class="section">
        <div class="container-x">
            {{-- Calculated profile --}}
            <div class="mb-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-panel p-6"><p class="text-sm text-muted">{{ __('site.plan.bmi') }}</p><p class="font-serif text-4xl">{{ $plan->bmi() }}</p></div>
                <div class="bg-panel p-6"><p class="text-sm text-muted">{{ __('site.plan.goal') }}</p><p class="font-serif text-2xl">{{ __('site.plan.goal_'.$plan->goal) }}</p></div>
                <div class="bg-panel p-6"><p class="text-sm text-muted">{{ __('site.plan.calc_calories') }}</p><p class="font-serif text-4xl">{{ $plan->targetCalories() }}</p></div>
                <div class="bg-panel p-6"><p class="text-sm text-muted">{{ __('site.plan.activity') }}</p><p class="font-serif text-2xl">{{ __('site.plan.activity_'.$plan->activity) }}</p></div>
            </div>

            @if ($plan->status === 'pending')
                <div data-plan-status="{{ route('plan.status', $plan) }}" class="flex flex-col items-center py-20 text-center">
                    <div class="h-16 w-16 animate-spin rounded-full border-4 border-brand-light border-t-brand"></div>
                    <h2 class="mt-8 text-3xl">{{ __('site.plan.generating') }}</h2>
                    <p class="mt-3 max-w-md text-muted">{{ __('site.plan.generating_text') }}</p>
                </div>
            @elseif ($plan->status === 'failed')
                <div class="mx-auto max-w-xl border-l-4 border-red-600 bg-red-50 p-8">
                    <h2 class="text-2xl text-red-700">{{ __('site.plan.failed') }}</h2>
                    <p class="mt-2 text-sm text-red-700/80">{{ __('site.plan.failed_text') }}</p>
                    <a href="{{ route('plan.create') }}" class="btn-primary mt-6">{{ __('site.plan.try_again') }}</a>
                </div>
            @else
                @php($r = $plan->result)

                <div class="grid gap-10 lg:grid-cols-[1fr_340px]">
                    <div>
                        <p class="text-lg leading-relaxed">{{ $r['summary'] ?? '' }}</p>
                        @if (! empty($r['warning']))
                            <div class="mt-6 border-l-4 border-amber-500 bg-amber-50 p-4 text-sm">⚠️ {{ $r['warning'] }}</div>
                        @endif

                        <div class="mt-10 space-y-8">
                            @foreach ($r['days'] ?? [] as $day)
                                <details class="group bg-panel" @if ($loop->first) open @endif>
                                    <summary class="flex cursor-pointer list-none items-center justify-between p-6">
                                        <h3 class="text-2xl">{{ $day['day'] }}</h3>
                                        <span class="text-sm text-muted">
                                            {{ collect($day['meals'])->sum('calories') }} {{ __('site.macros.kcal') }}
                                            <span class="ml-3 inline-block transition group-open:rotate-180">⌄</span>
                                        </span>
                                    </summary>
                                    <div class="grid gap-px bg-line md:grid-cols-2">
                                        @foreach ($day['meals'] as $meal)
                                            @php($recipe = $recipes[$meal['recipe_slug'] ?? ''] ?? null)
                                            <div class="flex gap-4 bg-white p-6">
                                                @if ($recipe)
                                                    <x-image :src="$recipe->imageUrl()" :alt="$recipe->name" class="h-20 w-20 shrink-0" />
                                                @endif
                                                <div class="min-w-0 flex-1">
                                                    <p class="eyebrow">{{ __('site.plan.meal_'.$meal['type']) }}</p>
                                                    <h4 class="mt-1 font-serif text-lg">{{ $meal['title'] }}</h4>
                                                    <p class="mt-1 text-sm text-muted">{{ $meal['description'] }}</p>
                                                    <p class="mt-2 text-xs">
                                                        <b>{{ $meal['calories'] }}</b> {{ __('site.macros.kcal') }} ·
                                                        {{ __('site.macros.p') }} {{ $meal['protein'] }} ·
                                                        {{ __('site.macros.f') }} {{ $meal['fat'] }} ·
                                                        {{ __('site.macros.c') }} {{ $meal['carbs'] }}
                                                    </p>
                                                    @if ($recipe)
                                                        <a href="{{ route('dishes.show', $recipe) }}" class="mt-2 inline-block text-xs font-medium uppercase text-brand hover:underline">{{ __('site.plan.open_recipe') }} →</a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>

                    <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
                        <div class="bg-brand p-8 text-white">
                            <p class="text-sm text-white/70">{{ __('site.plan.daily_target') }}</p>
                            <p class="font-serif text-5xl">{{ $r['daily_calories'] ?? '' }} <span class="text-xl">{{ __('site.macros.kcal') }}</span></p>
                            <div class="mt-6 grid grid-cols-3 gap-2 text-center">
                                <div class="bg-white/10 p-3"><b class="block text-xl">{{ $r['protein_g'] ?? '' }}</b><span class="text-xs">{{ __('site.macros.protein') }}, {{ __('site.macros.g') }}</span></div>
                                <div class="bg-white/10 p-3"><b class="block text-xl">{{ $r['fat_g'] ?? '' }}</b><span class="text-xs">{{ __('site.macros.fat') }}, {{ __('site.macros.g') }}</span></div>
                                <div class="bg-white/10 p-3"><b class="block text-xl">{{ $r['carbs_g'] ?? '' }}</b><span class="text-xs">{{ __('site.macros.carbs') }}, {{ __('site.macros.g') }}</span></div>
                            </div>
                            <p class="mt-4 text-sm">💧 {{ __('site.plan.water', ['l' => $r['water_l'] ?? 2]) }}</p>
                        </div>

                        @if (! empty($r['tips']))
                            <div class="bg-panel p-8">
                                <h3 class="card-title">{{ __('site.plan.tips') }}</h3>
                                <ul class="space-y-3 text-sm text-muted">
                                    @foreach ($r['tips'] as $tip)
                                        <li class="flex gap-3"><span class="text-brand">✔</span>{{ $tip }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (! empty($r['shopping_list']))
                            <div class="border border-line p-8">
                                <h3 class="card-title">{{ __('site.plan.shopping_list') }}</h3>
                                <ul class="columns-2 gap-4 space-y-1 text-sm text-muted">
                                    @foreach ($r['shopping_list'] as $item)
                                        <li>• {{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <button onclick="window.print()" class="btn-ghost w-full">🖨 {{ __('site.plan.print') }}</button>
                    </aside>
                </div>
            @endif
        </div>
    </section>
@endsection
