@extends('layouts.app')

@section('title', __('site.nav.ai_plan'))

@section('content')
    <x-page-hero :title="__('site.plan.title')" :subtitle="__('site.plan.subtitle')"
                 image="https://images.unsplash.com/photo-1494390248081-4e521a5940db?w=1600&q=70&auto=format&fit=crop" />

    <section class="section">
        <div class="container-x grid gap-12 lg:grid-cols-[1fr_380px]">
            <form method="post" action="{{ route('plan.store') }}" class="space-y-10">
                @csrf

                @if ($errors->any())
                    <div class="border-l-4 border-red-600 bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <fieldset>
                    <legend class="mb-6 font-serif text-3xl">1. {{ __('site.plan.about_you') }}</legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <span class="label">{{ __('site.plan.gender') }} *</span>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach (['female', 'male'] as $g)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="gender" value="{{ $g }}" class="peer sr-only" @checked(old('gender', 'female') === $g) required>
                                        <span class="block border border-line px-4 py-3 text-center text-sm transition peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white">{{ __('site.plan.gender_'.$g) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="label" for="age">{{ __('site.plan.age') }} *</label>
                            <input id="age" name="age" type="number" min="14" max="99" value="{{ old('age', 30) }}" class="field" required>
                        </div>
                        <div>
                            <label class="label" for="height_cm">{{ __('site.plan.height') }} *</label>
                            <input id="height_cm" name="height_cm" type="number" min="120" max="230" value="{{ old('height_cm', 170) }}" class="field" required>
                        </div>
                        <div>
                            <label class="label" for="weight_kg">{{ __('site.plan.weight') }} *</label>
                            <input id="weight_kg" name="weight_kg" type="number" step="0.1" min="30" max="300" value="{{ old('weight_kg', 70) }}" class="field" required>
                        </div>
                        <div>
                            <label class="label" for="target_weight_kg">{{ __('site.plan.target_weight') }}</label>
                            <input id="target_weight_kg" name="target_weight_kg" type="number" step="0.1" min="30" max="300" value="{{ old('target_weight_kg') }}" class="field">
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-6 font-serif text-3xl">2. {{ __('site.plan.goal_activity') }}</legend>
                    <span class="label">{{ __('site.plan.goal') }} *</span>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach (\App\Models\DietPlanRequest::GOALS as $goal)
                            <label class="cursor-pointer">
                                <input type="radio" name="goal" value="{{ $goal }}" class="peer sr-only" @checked(old('goal', 'lose') === $goal) required>
                                <span class="block h-full border border-line px-3 py-4 text-center text-sm transition peer-checked:border-brand peer-checked:bg-brand-light peer-checked:text-brand-dark">
                                    <span class="mb-1 block text-2xl">{{ ['lose' => '⚖️', 'maintain' => '🧘', 'gain' => '💪', 'health' => '❤️'][$goal] }}</span>
                                    {{ __('site.plan.goal_'.$goal) }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="label" for="activity">{{ __('site.plan.activity') }} *</label>
                            <select id="activity" name="activity" class="field" required>
                                @foreach (\App\Models\DietPlanRequest::ACTIVITIES as $level)
                                    <option value="{{ $level }}" @selected(old('activity', 'light') === $level)>{{ __('site.plan.activity_'.$level) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="meals_per_day">{{ __('site.plan.meals_per_day') }} *</label>
                            <select id="meals_per_day" name="meals_per_day" class="field" required>
                                @foreach ([3, 4, 5, 6] as $n)
                                    <option value="{{ $n }}" @selected(old('meals_per_day', 4) == $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="preferred_diet_id">{{ __('site.plan.preferred_diet') }}</label>
                            <select id="preferred_diet_id" name="preferred_diet_id" class="field">
                                <option value="">{{ __('site.plan.no_preference') }}</option>
                                @foreach ($diets as $diet)
                                    <option value="{{ $diet->id }}" @selected(old('preferred_diet_id', $selectedDiet) == $diet->id)>{{ $diet->icon }} {{ $diet->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-6 font-serif text-3xl">3. {{ __('site.plan.food') }}</legend>
                    <div class="grid gap-5">
                        <div>
                            <label class="label" for="allergies">{{ __('site.plan.allergies') }}</label>
                            <textarea id="allergies" name="allergies" rows="2" class="field" placeholder="{{ __('site.plan.allergies_placeholder') }}">{{ old('allergies') }}</textarea>
                        </div>
                        <div>
                            <label class="label" for="preferences">{{ __('site.plan.preferences') }}</label>
                            <textarea id="preferences" name="preferences" rows="3" class="field" placeholder="{{ __('site.plan.preferences_placeholder') }}">{{ old('preferences') }}</textarea>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="label" for="name">{{ __('site.plan.name') }}</label>
                                <input id="name" name="name" value="{{ old('name') }}" class="field">
                            </div>
                            <div>
                                <label class="label" for="email">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" class="field">
                            </div>
                        </div>
                    </div>
                </fieldset>

                <div class="flex flex-wrap items-center gap-6">
                    <button class="btn-primary">✨ {{ __('site.plan.submit') }}</button>
                    <p class="max-w-md text-xs text-muted">{{ __('site.plan.disclaimer') }}</p>
                </div>
            </form>

            <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
                <div class="bg-brand p-8 text-white">
                    <h3 class="text-2xl">{{ __('site.plan.what_you_get') }}</h3>
                    <ul class="mt-5 space-y-3 text-sm text-white/90">
                        @foreach (['get1', 'get2', 'get3', 'get4'] as $k)
                            <li class="flex gap-3"><span>✔</span>{{ __('site.plan.'.$k) }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="bg-panel p-8 text-sm text-muted">
                    <h3 class="mb-3 font-serif text-xl text-ink">{{ __('site.plan.how_title') }}</h3>
                    <p>{{ __('site.plan.how_text') }}</p>
                </div>
            </aside>
        </div>
    </section>
@endsection
