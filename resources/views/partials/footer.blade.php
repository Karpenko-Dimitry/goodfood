<footer class="relative bg-[#1b2410] text-white/70 print:hidden">
    <div class="container-x grid gap-12 py-16 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <h4 class="mb-5 text-2xl text-white">{{ __('site.footer.about') }}</h4>
            <p class="text-sm leading-relaxed">{{ __('site.footer.about_text') }}</p>
            <a href="{{ route('home') }}" class="mt-6 inline-block"><x-logo /></a>
        </div>
        <div>
            <h4 class="mb-5 text-2xl text-white">{{ __('site.footer.links') }}</h4>
            <ul class="space-y-2 text-sm">
                <li><a class="hover:text-white" href="{{ route('home') }}">{{ __('site.nav.home') }}</a></li>
                <li><a class="hover:text-white" href="{{ route('diets.index') }}">{{ __('site.nav.diets') }}</a></li>
                <li><a class="hover:text-white" href="{{ route('dishes.index') }}">{{ __('site.nav.recipes') }}</a></li>
                <li><a class="hover:text-white" href="{{ route('plan.create') }}">{{ __('site.nav.ai_plan') }}</a></li>
                <li><a class="hover:text-white" href="{{ route('about') }}">{{ __('site.nav.about') }}</a></li>
            </ul>
        </div>
        <div>
            <h4 class="mb-5 text-2xl text-white">{{ __('site.footer.contact') }}</h4>
            <ul class="space-y-2 text-sm">
                <li>{{ __('site.contact.address') }}</li>
                <li><a class="hover:text-white" href="tel:+1234567890">+ 123 456 7890</a></li>
                <li><a class="hover:text-white" href="mailto:hello@dietcoach.test">hello@dietcoach.test</a></li>
            </ul>
        </div>
        <div>
            <h4 class="mb-5 text-2xl text-white">{{ __('site.footer.newsletter') }}</h4>
            <p class="mb-4 text-sm">{{ __('site.footer.newsletter_text') }}</p>
            <form class="flex" onsubmit="event.preventDefault(); this.querySelector('button').textContent='✓';">
                <input type="email" required placeholder="Email" class="w-full min-w-0 bg-white/10 px-4 py-3 text-sm text-white placeholder-white/50 outline-none focus:bg-white/15">
                <button class="bg-brand px-5 text-white hover:bg-brand-dark" aria-label="Subscribe">→</button>
            </form>
        </div>
    </div>
    <div class="border-t border-white/10 py-6 text-center text-sm">
        © {{ date('Y') }} DietCoach — {{ __('site.footer.rights') }}
    </div>
</footer>
