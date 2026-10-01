@props(['protein' => 0, 'fat' => 0, 'carbs' => 0])
@php
    $kcal = [$protein * 4, $fat * 9, $carbs * 4];
    $sum = array_sum($kcal) ?: 1;
@endphp
<div {{ $attributes->merge(['class' => 'flex h-2 w-full overflow-hidden rounded-full bg-line']) }} title="{{ __('site.macros.protein') }} / {{ __('site.macros.fat') }} / {{ __('site.macros.carbs') }}">
    <span class="bg-brand" style="width: {{ $kcal[0] / $sum * 100 }}%"></span>
    <span class="bg-amber-400" style="width: {{ $kcal[1] / $sum * 100 }}%"></span>
    <span class="bg-sky-400" style="width: {{ $kcal[2] / $sum * 100 }}%"></span>
</div>
