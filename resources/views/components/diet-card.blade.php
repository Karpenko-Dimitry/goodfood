@props(['diet'])
<article class="card reveal flex flex-col">
    <div class="mb-6 flex items-center justify-between">
        <span class="text-4xl" aria-hidden="true">{{ $diet->icon ?: '🥗' }}</span>
        <span class="flex gap-1" title="{{ __('site.diet.difficulty') }}">
            @for ($i = 1; $i <= 3; $i++)
                <span class="h-2 w-5 {{ $i <= $diet->difficulty ? 'bg-brand' : 'bg-line' }}"></span>
            @endfor
        </span>
    </div>
    <h3 class="card-title">{{ $diet->name }}</h3>
    <p class="flex-1 text-sm leading-relaxed text-muted">{{ $diet->excerpt }}</p>
    @if ($diet->calories_min)
        <p class="mt-4 text-xs text-muted">{{ $diet->calories_min }}–{{ $diet->calories_max }} {{ __('site.macros.kcal_day') }}</p>
    @endif
    <a href="{{ route('diets.show', $diet) }}" class="btn-primary mt-6 self-start">{{ __('site.read_more') }}</a>
</article>
