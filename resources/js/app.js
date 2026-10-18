import './bootstrap';

// Mobile menu
document.querySelectorAll('[data-menu-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => document.querySelector('[data-menu]').classList.toggle('hidden'));
});

// Dropdowns (language picker): touch screens have no hover, and iOS Safari does not focus buttons on tap.
document.querySelectorAll('[data-dropdown]').forEach((root) => {
    const toggle = root.querySelector('[data-dropdown-toggle]');
    const setOpen = (open) => {
        root.toggleAttribute('data-open', open);
        toggle.setAttribute('aria-expanded', String(open));
    };

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        setOpen(!root.hasAttribute('data-open'));
    });
    document.addEventListener('click', (e) => root.contains(e.target) || setOpen(false));
    document.addEventListener('keydown', (e) => e.key === 'Escape' && setOpen(false));
});

// Fade-in on scroll (the template uses Elementor entrance animations)
const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
        if (e.isIntersecting) {
            e.target.classList.add('is-visible');
            io.unobserve(e.target);
        }
    });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach((el) => io.observe(el));

// Back-to-top button
const toTop = document.querySelector('[data-to-top]');
if (toTop) {
    window.addEventListener('scroll', () => toTop.classList.toggle('opacity-0', window.scrollY < 400));
    toTop.addEventListener('click', () => window.scrollTo({ top: 0 }));
}

// Poll AI plan status
const pending = document.querySelector('[data-plan-status]');
if (pending) {
    const poll = async () => {
        const { data } = await window.axios.get(pending.dataset.planStatus);
        // Reload when the plan is ready, or when another generated recipe has been saved.
        if (data.status !== 'pending' && String(data.dishes_pending) !== (pending.dataset.pending ?? '')) {
            return window.location.reload();
        }
        setTimeout(poll, 4000);
    };
    setTimeout(poll, 4000);
}

// Tabs on the home page
document.querySelectorAll('[data-tabs]').forEach((root) => {
    const tabs = root.querySelectorAll('[data-tab]');
    tabs.forEach((tab) => tab.addEventListener('click', () => {
        tabs.forEach((t) => {
            const active = t === tab;
            t.classList.toggle('bg-brand', active);
            t.classList.toggle('text-white', active);
            t.classList.toggle('border-brand', active);
            t.classList.toggle('bg-panel', !active);
            t.classList.toggle('border-transparent', !active);
        });
        root.querySelectorAll('[data-panel]').forEach((p) => p.classList.toggle('hidden', p.dataset.panel !== tab.dataset.tab));
    }));
});
