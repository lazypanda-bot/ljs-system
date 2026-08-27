document.addEventListener('DOMContentLoaded', function () {
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