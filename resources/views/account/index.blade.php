@extends('layouts.app')

@section('title', __('site.auth.account'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="section">
        <div class="container-x">
            @if (session('status'))
                <div class="mb-8 border-l-4 border-brand bg-brand-light p-4 text-sm">{{ session('status') }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    @if ($user->avatar)
                        <img src="{{ $user->avatar }}" alt="" class="h-14 w-14 rounded-full" referrerpolicy="no-referrer">
                    @else
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand font-serif text-2xl text-white">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    @endif
                    <div>
                        <h1 class="text-3xl">{{ __('site.auth.hello', ['name' => $user->name]) }}</h1>
                        <p class="text-sm text-muted">{{ $user->email }}@if ($user->google_id) · {{ __('site.auth.signed_with_google') }}@endif</p>
                    </div>
                </div>
                <a href="{{ route('plan.create') }}" class="btn-primary">✨ {{ __('site.auth.create_plan') }}</a>
            </div>

            <h2 class="mt-14 text-3xl">{{ __('site.auth.my_plans') }}</h2>

            @if ($plans->isEmpty())
                <p class="mt-6 text-muted">{{ __('site.auth.no_plans') }}</p>
            @else
                <div class="mt-6 divide-y divide-line border border-line">
                    @foreach ($plans as $plan)
                        <a href="{{ route('plan.show', $plan) }}" class="flex flex-wrap items-center justify-between gap-4 p-5 transition hover:bg-panel/60">
                            <div>
                                <p class="font-serif text-xl">{{ __('site.plan.goal_'.$plan->goal) }}@if ($plan->preferredDiet) · {{ $plan->preferredDiet->name }}@endif</p>
                                <p class="text-sm text-muted">{{ $plan->created_at->translatedFormat('d F Y, H:i') }}@if ($plan->result) · {{ $plan->result['daily_calories'] ?? '' }} {{ __('site.macros.kcal') }}@endif</p>
                            </div>
                            <span @class(['chip', '!bg-amber-100 !text-amber-800' => $plan->status === 'pending', '!bg-red-100 !text-red-700' => $plan->status === 'failed'])>
                                {{ __('site.auth.plan_status_'.$plan->status) }}
                            </span>
                        </a>
                    @endforeach
                </div>
                <div class="mt-8">{{ $plans->links() }}</div>
            @endif
        </div>
    </section>
@endsection
