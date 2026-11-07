document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-featured-photos]').forEach((gallery) => {
        const counter = gallery.parentElement.querySelector('[data-featured-photo-count]');
        const total = gallery.querySelectorAll('img').length;
        const updateCounter = () => {
            if (!counter || !gallery.clientWidth) return;
            const index = Math.min(total, Math.max(1, Math.round(gallery.scrollLeft / gallery.clientWidth) + 1));
            counter.textContent = `${index}/${total}`;
        };
        gallery.addEventListener('scroll', updateCounter, { passive: true });
        gallery.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
            if (!window.matchMedia('(max-width: 1024px)').matches) return;
            event.preventDefault();
            gallery.scrollBy({ left: (event.key === 'ArrowRight' ? 1 : -1) * gallery.clientWidth, behavior: 'instant' });
        });
        window.addEventListener('resize', () => {
            gallery.scrollLeft = 0;
            updateCounter();
        });
        updateCounter();
    });

    const track = document.getElementById('rushSliderTrack');
    const btnLeft = document.getElementById('slideLeft');
    const btnRight = document.getElementById('slideRight');

    if (track && btnLeft && btnRight) {
        const scrollAmount = 310; // Card width + gap offset

        btnLeft.addEventListener('click', () => {
            track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });

        btnRight.addEventListener('click', () => {
            track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });
    }
});
