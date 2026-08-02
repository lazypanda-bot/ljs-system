document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('[data-sidebar]');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    const openLoginBtn = document.getElementById('openLoginModal');
    const loginModalOverlay = document.getElementById('loginModalOverlay');
    const loginModalClose = document.querySelector('.login-modal-close');

    const closeLoginModal = () => {
        if (!loginModalOverlay) {
            return;
        }
        loginModalOverlay.classList.remove('open');
        loginModalOverlay.setAttribute('aria-hidden', 'true');
    };

    if (openLoginBtn && loginModalOverlay) {
        openLoginBtn.addEventListener('click', () => {
            loginModalOverlay.classList.add('open');
            loginModalOverlay.setAttribute('aria-hidden', 'false');
        });
    }

    if (loginModalClose) {
        loginModalClose.addEventListener('click', closeLoginModal);
    }

    if (loginModalOverlay) {
        loginModalOverlay.addEventListener('click', (event) => {
            if (event.target === loginModalOverlay) {
                closeLoginModal();
            }
        });

        if (loginModalOverlay.dataset.openIfError === 'true') {
            loginModalOverlay.classList.add('open');
            loginModalOverlay.setAttribute('aria-hidden', 'false');
        }
    }

    const profileMenuButton = document.getElementById('profileMenuButton');
    const profileMenu = document.getElementById('profileMenu');

    if (profileMenuButton && profileMenu) {
        profileMenuButton.addEventListener('click', () => {
            const isOpen = profileMenu.classList.toggle('open');
            profileMenu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            profileMenuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', (event) => {
            if (!profileMenu.contains(event.target) && !profileMenuButton.contains(event.target)) {
                profileMenu.classList.remove('open');
                profileMenu.setAttribute('aria-hidden', 'true');
                profileMenuButton.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
