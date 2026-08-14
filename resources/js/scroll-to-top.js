document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('scroll-to-top');

    if (!button) {
        return;
    }

    const updateVisibility = () => {
        const isVisible = window.scrollY > 500;
        button.classList.toggle('hidden', !isVisible);
        button.classList.toggle('flex', isVisible);
    };

    button.addEventListener('click', () => {
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
    });

    window.addEventListener('scroll', updateVisibility, { passive: true });
    updateVisibility();
});
