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
            {{-- Opens on tap/click via JS (data-dropdown); hover/keyboard focus also work on desktop. --}}
            <div class="group relative" data-dropdown>
                <button type="button" data-dropdown-toggle aria-haspopup="true" aria-expanded="false" aria-label="{{ __('site.language') }}"
                        class="flex items-center gap-1 border border-white/40 px-3 py-1.5 text-sm uppercase text-white">
                    {{ app()->getLocale() }}
                    <svg viewBox="0 0 24 24" class="h-4 w-4 transition group-data-[open]:rotate-180" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="invisible absolute right-0 top-full z-50 min-w-40 bg-white py-2 opacity-0 shadow-xl transition group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100 group-data-[open]:visible group-data-[open]:opacity-100">
                    @foreach (config('app.locales') as $code => $label)
                        <a href="{{ localized_url($code) }}" hreflang="{{ $code }}"
                           class="block px-4 py-2 text-sm hover:bg-brand-light {{ $code === app()->getLocale() ? 'font-semibold text-brand' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('plan.create') }}" class="hidden whitespace-nowrap bg-white px-5 py-2.5 text-sm font-medium uppercase text-brand transition hover:bg-brand-light xl:inline-block">
                {{ __('site.cta.get_plan') }}
            </a>
            @auth
                <div class="group relative" data-dropdown>
                    <button type="button" data-dropdown-toggle aria-haspopup="true" aria-expanded="false" aria-label="{{ __('site.auth.account') }}"
                            class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-white/40 bg-white/10 text-sm font-medium text-white">
                        @if (auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="" class="h-full w-full object-cover" referrerpolicy="no-referrer">
                        @else
                            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </button>
                    {{-- Opens on click only (data-dropdown JS), not on hover. --}}
                    <div class="invisible absolute right-0 top-full z-50 mt-1 min-w-52 bg-white py-2 opacity-0 shadow-xl transition group-data-[open]:visible group-data-[open]:opacity-100">
                        <p class="truncate border-b border-line px-4 pb-2 text-xs text-muted">{{ auth()->user()->email }}</p>
                        <a href="{{ route('account') }}" class="block px-4 py-2 text-sm hover:bg-brand-light">{{ __('site.auth.my_plans') }}</a>
                        @if (auth()->user()->is_admin)
                            <a href="{{ url('/admin') }}" class="block px-4 py-2 text-sm hover:bg-brand-light">{{ __('site.auth.admin') }}</a>
                        @endif
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="block w-full px-4 py-2 text-left text-sm hover:bg-brand-light">{{ __('site.auth.logout') }}</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="whitespace-nowrap text-sm text-white/90 hover:text-white">{{ __('site.auth.login') }}</a>
            @endauth
            <button data-menu-toggle class="text-white xl:hidden" aria-label="Menu">
                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h10M4 18h16"/></svg>
            </button>
        </div>
    </div>
    <nav data-menu class="hidden border-t border-white/20 bg-brand-dark xl:hidden">
        @foreach ($nav as $route => $label)
            <a href="{{ route($route) }}" class="block px-6 py-3 text-white/90 hover:bg-brand">{{ $label }}</a>
        @endforeach
        <div class="border-t border-white/20">
            @auth
                <a href="{{ route('account') }}" class="block px-6 py-3 text-white/90 hover:bg-brand">{{ __('site.auth.my_plans') }}</a>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="block w-full px-6 py-3 text-left text-white/90 hover:bg-brand">{{ __('site.auth.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block px-6 py-3 text-white/90 hover:bg-brand">{{ __('site.auth.login') }}</a>
                <a href="{{ route('register') }}" class="block px-6 py-3 text-white/90 hover:bg-brand">{{ __('site.auth.register') }}</a>
            @endauth
        </div>
        <div class="flex gap-2 border-t border-white/20 px-6 py-4">
            @foreach (config('app.locales') as $code => $label)
                <a href="{{ localized_url($code) }}" hreflang="{{ $code }}"
                   class="border px-3 py-1.5 text-sm {{ $code === app()->getLocale() ? 'border-white bg-white text-brand' : 'border-white/40 text-white' }}">{{ $label }}</a>
            @endforeach
        </div>
    </nav>
</header>
