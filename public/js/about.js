document.addEventListener('DOMContentLoaded', function () {
    const aboutBtn = document.querySelector('.about-banner-btn');

    if (aboutBtn) {
        aboutBtn.addEventListener('click', function (e) {
            console.log('Navigating to About Us page...');
        });
    }
});