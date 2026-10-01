@extends('layouts.app')

@section('title', __('site.nav.diets'))

@section('content')
    <x-page-hero :title="__('site.nav.diets')" :subtitle="__('site.diets.subtitle')"
                 image="https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1600&q=70&auto=format&fit=crop" />

    <section class="section">
        <div class="container-x">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($diets as $diet)
                    <article class="reveal group flex flex-col bg-panel transition hover:-translate-y-1 hover:shadow-xl">
                        <a href="{{ route('diets.show', $diet) }}" class="relative block aspect-[16/10] overflow-hidden">
                            <x-image :src="$diet->imageUrl()" :alt="$diet->name" class="h-full w-full transition duration-500 group-hover:scale-105" />
                            <span class="absolute left-4 top-4 flex h-12 w-12 items-center justify-center bg-white text-2xl">{{ $diet->icon ?: '🥗' }}</span>
                        </a>
                        <div class="flex flex-1 flex-col p-8">
                            <h2 class="card-title"><a href="{{ route('diets.show', $diet) }}" class="hover:text-brand">{{ $diet->name }}</a></h2>
                            <p class="flex-1 text-sm leading-relaxed text-muted">{{ $diet->excerpt }}</p>
                            <div class="mt-6 flex flex-wrap gap-2">
                                <span class="chip">{{ __('site.diet.difficulty') }}: {{ __('site.diet.level_'.$diet->difficulty) }}</span>
                                @if ($diet->protein_pct)
                                    <span class="chip">{{ __('site.macros.p') }} {{ $diet->protein_pct }}% · {{ __('site.macros.f') }} {{ $diet->fat_pct }}% · {{ __('site.macros.c') }} {{ $diet->carbs_pct }}%</span>
                                @endif
                            </div>
                            <a href="{{ route('diets.show', $diet) }}" class="btn-primary mt-6 self-start">{{ __('site.read_more') }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-12">{{ $diets->links() }}</div>
        </div>
    </section>
@endsection
