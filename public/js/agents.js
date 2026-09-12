document.addEventListener('DOMContentLoaded', function () {
    const track = document.getElementById('agentSliderTrack');
    const leftBtn = document.getElementById('agentSlideLeft');
    const rightBtn = document.getElementById('agentSlideRight');

    if (track && leftBtn && rightBtn) {
        leftBtn.addEventListener('click', () => {
            track.scrollBy({ left: -280, behavior: 'smooth' });
        });

        rightBtn.addEventListener('click', () => {
            track.scrollBy({ left: 280, behavior: 'smooth' });
        });
    }
});