@extends('layouts.app')

@section('title', __('site.nav.about'))

@section('content')
    <x-page-hero :title="__('site.nav.about')" image="https://images.unsplash.com/photo-1505253716362-afaea1d3d1af?w=1600&q=70&auto=format&fit=crop" />

    <section class="section">
        <div class="container-x grid items-center gap-12 lg:grid-cols-2">
            <img src="https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1000&q=80&auto=format&fit=crop" alt="" class="aspect-[4/3] w-full object-cover">
            <div>
                <p class="eyebrow">{{ __('site.about.eyebrow') }}</p>
                <h2 class="mt-3 text-4xl sm:text-5xl">{{ __('site.about.title') }}</h2>
                <p class="mt-6 leading-relaxed text-muted">{{ __('site.about.text1') }}</p>
                <p class="mt-4 leading-relaxed text-muted">{{ __('site.about.text2') }}</p>
                <a href="{{ route('plan.create') }}" class="btn-primary mt-8">{{ __('site.cta.get_plan') }}</a>
            </div>
        </div>
    </section>

    <section class="section bg-panel/60">
        <div class="container-x grid gap-8 md:grid-cols-3">
            @foreach (['value1' => '🥦', 'value2' => '🔬', 'value3' => '🤖'] as $key => $icon)
                <div class="card reveal bg-white">
                    <span class="text-4xl">{{ $icon }}</span>
                    <h3 class="card-title mt-4">{{ __("site.about.{$key}_title") }}</h3>
                    <p class="text-sm leading-relaxed text-muted">{{ __("site.about.{$key}_text") }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
