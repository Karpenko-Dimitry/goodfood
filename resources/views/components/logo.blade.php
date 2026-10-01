@props(['light' => true])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <svg viewBox="0 0 40 40" class="h-9 w-9" fill="none" aria-hidden="true">
        <circle cx="20" cy="20" r="19" stroke="currentColor" stroke-width="2" class="{{ $light ? 'text-white' : 'text-brand' }}"/>
        <path d="M20 31c0-8 2-13 9-17-1 8-4 13-9 17Z" fill="currentColor" class="{{ $light ? 'text-white' : 'text-brand' }}"/>
        <path d="M20 31c0-6-2-10-8-13 1 6 3 10 8 13Z" fill="currentColor" opacity=".7" class="{{ $light ? 'text-white' : 'text-brand' }}"/>
        <circle cx="16" cy="11" r="3" fill="currentColor" class="{{ $light ? 'text-white' : 'text-brand' }}"/>
    </svg>
    <span class="font-serif text-2xl {{ $light ? 'text-white' : 'text-ink' }}">DietCoach</span>
</span>
