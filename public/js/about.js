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

document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('.contact-icon-card');

    cards.forEach(card => {
        // When clicked, lock it open or open the link if already active
        card.addEventListener('click', function (e) {
            e.stopPropagation();
            const isActive = this.classList.contains('active');
            
            // Close all others first
            cards.forEach(c => c.classList.remove('active'));

            if (!isActive) {
                // Lock this one open
                this.classList.add('active');
            } else {
                // If clicked again when already active, trigger link action
                const link = this.getAttribute('data-link');
                if (link) {
                    if (this.getAttribute('data-external')) {
                        window.open(link, '_blank');
                    } else {
                        window.location.href = link;
                    }
                }
            }
        });

        // Hovering over ANY card clears active states so hover takes over seamlessly
        card.addEventListener('mouseenter', function () {
            cards.forEach(c => c.classList.remove('active'));
        });
    });

    // Clicking outside closes all active/locked cards
    document.addEventListener('click', function () {
        cards.forEach(c => c.classList.remove('active'));
    });
});