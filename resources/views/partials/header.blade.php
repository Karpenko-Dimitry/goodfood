@php
    $nav = [
        'home' => __('site.nav.home'),
        'diets.index' => __('site.nav.diets'),
        'dishes.index' => __('site.nav.recipes'),
        'plan.create' => __('site.nav.ai_plan'),
        'about' => __('site.nav.about'),
        'contact' => __('site.nav.contact'),
    ];
    $current = request()->route()?->getName();
@endphp
<header class="sticky top-0 z-50 bg-brand shadow-md print:hidden">
    <div class="container-x flex h-20 items-center justify-between gap-6">
        <a href="{{ route('home') }}"><x-logo /></a>

        <nav class="hidden items-center gap-7 xl:flex">
            @foreach ($nav as $route => $label)
                <a href="{{ route($route) }}"
                   class="relative whitespace-nowrap py-2 text-[15px] text-white/90 transition hover:text-white {{ str_starts_with($current, explode('.', $route)[0]) ? 'text-white after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:bg-white' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-3">
            <div class="relative group">
                <button class="flex items-center gap-1 border border-white/40 px-3 py-1.5 text-sm uppercase text-white">
                    {{ app()->getLocale() }}
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="invisible absolute right-0 top-full min-w-40 bg-white py-2 opacity-0 shadow-xl transition group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100">
                    @foreach (config('app.locales') as $code => $label)
                        <a href="{{ localized_url($code) }}" hreflang="{{ $code }}"
                           class="block px-4 py-2 text-sm hover:bg-brand-light {{ $code === app()->getLocale() ? 'font-semibold text-brand' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('plan.create') }}" class="hidden whitespace-nowrap bg-white px-5 py-2.5 text-sm font-medium uppercase text-brand transition hover:bg-brand-light xl:inline-block">
                {{ __('site.cta.get_plan') }}
            </a>
            <button data-menu-toggle class="text-white xl:hidden" aria-label="Menu">
                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h10M4 18h16"/></svg>
            </button>
        </div>
    </div>
    <nav data-menu class="hidden border-t border-white/20 bg-brand-dark xl:hidden">
        @foreach ($nav as $route => $label)
            <a href="{{ route($route) }}" class="block px-6 py-3 text-white/90 hover:bg-brand">{{ $label }}</a>
        @endforeach
    </nav>
</header>
