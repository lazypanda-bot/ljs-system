document.addEventListener('DOMContentLoaded', function () {
    const aboutBtn = document.querySelector('.about-banner-btn');

    if (aboutBtn) {
        aboutBtn.addEventListener('click', function (e) {
            console.log('Navigating to About Us page');
        });
    }

    const heroSection = document.querySelector(".about-hero-section");
    const navbar = document.querySelector('.landing-nav');

    function checkScroll() {
        // Toggle the hero section visibility
        if (heroSection) {
            heroSection.classList.add("visible");
        }

        // Toggle sticky background on the navbar when scrolled past 50px
        if (navbar) {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
    }

    window.addEventListener("scroll", checkScroll);
    checkScroll();

    if (heroSection) {
        setTimeout(() => {
            heroSection.classList.add("visible");
        }, 50);
    }
});