document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('[data-sidebar]');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    const loginModalOverlay = document.getElementById('loginModalOverlay');
    const loginModalClose = document.querySelector('.login-modal-close');
    const openLoginBtns = document.querySelectorAll('#openLoginModal, [data-open-login-modal]');

    const closeLoginModal = () => {
        if (!loginModalOverlay) {
            return;
        }
        loginModalOverlay.classList.remove('open');
        loginModalOverlay.setAttribute('aria-hidden', 'true');
    };

    openLoginBtns.forEach((openLoginBtn) => {
        if (openLoginBtn && loginModalOverlay) {
            openLoginBtn.addEventListener('click', () => {
                loginModalOverlay.classList.add('open');
                loginModalOverlay.setAttribute('aria-hidden', 'false');
            });
        }
    });

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

    const authTabs = document.querySelectorAll('.auth-tab');
    const authPanels = document.querySelectorAll('.auth-panel');

    authTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const panelId = tab.dataset.authPanel;

            authTabs.forEach((item) => {
                const active = item === tab;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            authPanels.forEach((panel) => {
                const active = panel.id === panelId;
                panel.hidden = !active;
                panel.classList.toggle('active', active);
            });

            const title = document.getElementById('loginModalTitle');
            const subtitle = document.getElementById('authModalSubtitle');
            if (title && subtitle) {
                title.textContent = tab.dataset.authTitle;
                subtitle.textContent = tab.dataset.authSubtitle;
            }
        });
    });

    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const passwordInput = document.getElementById(button.dataset.passwordTarget);
            if (!passwordInput) {
                return;
            }

            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
            const isVisible = passwordInput.type === 'text';
            button.textContent = isVisible ? 'Hide' : 'Show';
            button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
        });
    });

    if (loginModalOverlay?.dataset.openIfError === 'true' && document.querySelector('.auth-input-error')) {
        document.querySelector('[data-auth-panel="modal-register-panel"]')?.click();
    }

    const notificationButton = document.querySelector('[data-notification-count-url]');
    if (notificationButton) {
        const notificationMenu = document.getElementById('notificationMenu');
        notificationButton.addEventListener('click', () => {
            const isOpen = notificationMenu?.classList.toggle('open');
            notificationMenu?.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            notificationButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        window.setInterval(async () => {
            const response = await fetch(notificationButton.dataset.notificationCountUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;

            const { count } = await response.json();
            let badge = notificationButton.querySelector('[data-notification-count]');
            if (count > 0 && !badge) {
                badge = document.createElement('span');
                badge.className = 'notification-count';
                badge.dataset.notificationCount = '';
                notificationButton.prepend(badge);
            }
            if (badge) {
                badge.textContent = count;
                badge.hidden = count === 0;
            }
        }, 10000);
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

    const publicProfileButton = document.getElementById('publicProfileButton');
    const publicProfileMenu = document.getElementById('publicProfileMenu');
    if (publicProfileButton && publicProfileMenu) {
        publicProfileButton.addEventListener('click', () => {
            const isOpen = publicProfileMenu.classList.toggle('open');
            publicProfileButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }
});
