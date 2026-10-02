document.addEventListener('DOMContentLoaded', () => {
    const publicNavToggle = document.getElementById('publicNavToggle');
    const publicNavigation = document.getElementById('publicNavigation');

    if (publicNavToggle && publicNavigation) {
        const closePublicNavigation = () => {
            publicNavigation.classList.remove('open');
            publicNavToggle.setAttribute('aria-expanded', 'false');
            publicNavToggle.setAttribute('aria-label', 'Open navigation menu');
        };

        publicNavToggle.addEventListener('click', () => {
            const isOpen = publicNavigation.classList.toggle('open');
            publicNavToggle.setAttribute('aria-expanded', String(isOpen));
            publicNavToggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
        });

        publicNavigation.querySelectorAll('a').forEach((link) => link.addEventListener('click', closePublicNavigation));
        document.addEventListener('click', (event) => {
            if (!publicNavigation.contains(event.target) && !publicNavToggle.contains(event.target)) closePublicNavigation();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && publicNavigation.classList.contains('open')) {
                closePublicNavigation();
                publicNavToggle.focus();
            }
        });
    }

    document.querySelectorAll('[data-width]').forEach((element) => {
        const width = Number(element.dataset.width);
        if (Number.isFinite(width)) element.style.setProperty('--bar-width', Math.max(0, Math.min(100, width)));
    });

    document.querySelectorAll('[data-fallback-src]').forEach((image) => {
        image.addEventListener('error', () => {
            image.src = image.dataset.fallbackSrc;
            image.removeAttribute('data-fallback-src');
        });
    });

    document.querySelectorAll('[data-thumbnail-target]').forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            const target = document.getElementById(thumbnail.dataset.thumbnailTarget);
            if (target) target.src = thumbnail.src;
        });
    });

    document.querySelectorAll('[data-auto-submit]').forEach((control) => {
        control.addEventListener('change', () => control.form?.requestSubmit());
    });

    document.querySelectorAll('.flash-toast').forEach((toast) => {
        const close = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(18px)';
            window.setTimeout(() => toast.remove(), 180);
        };
        toast.querySelector('.flash-toast-close')?.addEventListener('click', close);
        window.setTimeout(close, 5000);
    });

    const confirmableFormExclusions = [
        '/login', '/register', '/login/otp', '/messages', '/notifications',
    ];

    const getActionMessage = (form) => {
        const action = form.action.toLowerCase();
        const submitter = form.__confirmSubmitter;
        const value = (submitter?.value || form.querySelector('[name="status"]')?.value || '').toLowerCase();

        if (form.dataset.confirmMessage) return form.dataset.confirmMessage;
        if (action.includes('destroy') || action.includes('delete') || form.method.toLowerCase() === 'delete') {
            return 'Are you sure you want to delete this? This action cannot be undone.';
        }
        if (value.includes('reject') || value.includes('rejected')) return 'Are you sure you want to reject this request?';
        if (value.includes('approve') || value.includes('approved') || value.includes('live')) return 'Are you sure you want to approve this request?';
        if (action.includes('review')) return 'Are you sure you want to submit this review decision?';
        if (action.includes('assign') || action.includes('request')) return 'Are you sure you want to change this assignment?';
        if (action.includes('update') || action.includes('edit') || form.querySelector('input[name="_method"][value="PUT"], input[name="_method"][value="PATCH"]')) return 'Are you sure you want to save these changes?';
        return 'Are you sure you want to continue with this action?';
    };

    const shouldFocusCancel = (form) => {
        const action = form.action.toLowerCase();
        const message = getActionMessage(form).toLowerCase();
        return /(delete|destroy|remove|reject|cancel|cannot be undone)/.test(`${action} ${message}`);
    };

    const shouldConfirmForm = (form) => {
        if (form.dataset.noConfirm !== undefined || form.dataset.confirmed === 'true') return false;
        const method = (form.querySelector('input[name="_method"]')?.value || form.method || 'GET').toUpperCase();
        if (!['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) return false;
        const path = new URL(form.action, window.location.href).pathname;
        return !confirmableFormExclusions.some((excluded) => path === excluded || path.startsWith(`${excluded}/`));
    };

    const openConfirmation = (form) => new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'action-confirm-overlay';
        overlay.innerHTML = `<div class="action-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="actionConfirmTitle"><h2 id="actionConfirmTitle">Are you sure?</h2><p>${getActionMessage(form)}</p><div class="action-confirm-actions"><button type="button" class="action-confirm-cancel">No</button><button type="button" class="action-confirm-accept">Yes</button></div></div>`;
        document.body.appendChild(overlay);

        const finish = (confirmed) => {
            overlay.remove();
            resolve(confirmed);
        };
        overlay.querySelector('.action-confirm-cancel').addEventListener('click', () => finish(false));
        overlay.querySelector('.action-confirm-accept').addEventListener('click', () => finish(true));
        overlay.addEventListener('click', (event) => { if (event.target === overlay) finish(false); });
        overlay.querySelector(shouldFocusCancel(form) ? '.action-confirm-cancel' : '.action-confirm-accept').focus();
    });

    window.appConfirm = (message) => {
        const target = document.createElement('form');
        target.dataset.confirmMessage = message;
        return openConfirmation(target);
    };

    document.addEventListener('click', (event) => {
        const submitter = event.target.closest('button[type="submit"], input[type="submit"]');
        if (submitter?.form) submitter.form.__confirmSubmitter = submitter;
    }, true);

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !shouldConfirmForm(form)) return;
        event.preventDefault();
        if (await openConfirmation(form)) {
            form.dataset.confirmed = 'true';
            form.requestSubmit(form.__confirmSubmitter || undefined);
        }
    }, true);

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
    const authTabsContainer = document.querySelector('[data-auth-tabs]');
    const modalLoginForm = document.getElementById('modal-login-panel');
    const modalRegisterForm = document.getElementById('modal-register-panel');
    const modalOtpForm = document.getElementById('modal-otp-panel');
    const modalLoginStatus = document.getElementById('authLoginStatus');
    const modalRegisterStatus = document.getElementById('authRegisterStatus');
    const modalOtpStatus = document.getElementById('authOtpStatus');
    const modalOtpResend = document.getElementById('authOtpResend');
    const modalOtpBack = document.getElementById('authOtpBack');

    const showModalPanel = (panelId) => {
        authPanels.forEach((panel) => {
            const active = panel.id === panelId;
            panel.hidden = !active;
            panel.classList.toggle('active', active);
        });
        const isOtp = panelId === 'modal-otp-panel';
        if (authTabsContainer) authTabsContainer.hidden = isOtp;
        loginModalOverlay?.classList.toggle('otp-active', isOtp);
    };

    const setOtpStatus = (message, isError = false) => {
        if (!modalOtpStatus) return;
        modalOtpStatus.textContent = message;
        modalOtpStatus.classList.toggle('error', isError);
    };

    const setLoginStatus = (message) => {
        if (modalLoginStatus) modalLoginStatus.textContent = message;
    };

    const setRegisterStatus = (message) => {
        if (modalRegisterStatus) modalRegisterStatus.textContent = message;
    };

    const submitModalForm = async (form, onSuccess) => {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        });
        const data = await response.json().catch(() => null);
        if (!response.ok) {
            const message = data?.message || Object.values(data?.errors || {})?.flat()?.[0] || `Request failed (${response.status}).`;
            throw new Error(message);
        }
        if (!data) throw new Error(`The server returned an unexpected response (${response.status}).`);
        onSuccess(data);
    };

    modalLoginForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = modalLoginForm.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'Sending code...';
        try {
            await submitModalForm(modalLoginForm, (data) => {
                setLoginStatus('');
                const otpToken = modalOtpForm?.querySelector('input[name="_token"]');
                if (otpToken && data.csrf_token) otpToken.value = data.csrf_token;
                document.getElementById('loginModalTitle').textContent = 'Verify login';
                document.getElementById('authModalSubtitle').textContent = data.message;
                showModalPanel('modal-otp-panel');
                document.getElementById('modalOtpCode')?.focus();
            });
        } catch (error) {
            setLoginStatus(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'Login';
        }
    });

    modalRegisterForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = modalRegisterForm.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'Sending code...';
        try {
            await submitModalForm(modalRegisterForm, (data) => {
                setRegisterStatus('');
                const otpToken = modalOtpForm?.querySelector('input[name="_token"]');
                if (otpToken && data.csrf_token) otpToken.value = data.csrf_token;
                document.getElementById('loginModalTitle').textContent = 'Verify email';
                document.getElementById('authModalSubtitle').textContent = data.message;
                showModalPanel('modal-otp-panel');
                document.getElementById('modalOtpCode')?.focus();
            });
        } catch (error) {
            setRegisterStatus(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'Create Account';
        }
    });

    modalOtpForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = modalOtpForm.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'Verifying...';
        try {
            await submitModalForm(modalOtpForm, (data) => { window.location.href = data.redirect; });
        } catch (error) {
            setOtpStatus(error.message, true);
        } finally {
            button.disabled = false;
            button.textContent = 'Verify and continue';
        }
    });

    modalOtpResend?.addEventListener('click', async () => {
        modalOtpResend.disabled = true;
        setOtpStatus('Sending a new code...');
        try {
            await submitModalForm(modalOtpForm, (data) => setOtpStatus(data.message));
        } catch (error) {
            setOtpStatus(error.message, true);
        } finally {
            modalOtpResend.disabled = false;
        }
    });

    modalOtpBack?.addEventListener('click', () => {
        showModalPanel('modal-login-panel');
        document.getElementById('loginModalTitle').textContent = 'Login';
        document.getElementById('authModalSubtitle').textContent = 'Access your LJS Realty account.';
        setOtpStatus('');
    });

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

    const notificationButtons = [...document.querySelectorAll('[data-notification-count-url]')];
    const closeNotificationMenus = (except = null) => {
        notificationButtons.forEach((button) => {
            const menu = document.getElementById(button.dataset.notificationMenu || 'notificationMenu');
            if (button === except) return;
            menu?.classList.remove('open');
            menu?.setAttribute('aria-hidden', 'true');
            button.setAttribute('aria-expanded', 'false');
        });
    };

    notificationButtons.forEach((button) => {
        const menu = document.getElementById(button.dataset.notificationMenu || 'notificationMenu');
        button.addEventListener('click', () => {
            const isOpen = menu?.classList.contains('open') || false;
            closeNotificationMenus(button);
            menu?.classList.toggle('open', !isOpen);
            menu?.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
            button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.floating-notification-wrap, .notification-menu-wrap')) closeNotificationMenus();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeNotificationMenus();
    });

    if (notificationButtons.length) {
        window.setInterval(async () => {
            for (const button of notificationButtons) {
                try {
                    const response = await fetch(button.dataset.notificationCountUrl, { headers: { Accept: 'application/json' } });
                    if (!response.ok) continue;

                    const { count } = await response.json();
                    let badge = button.querySelector('[data-notification-count]');
                    if (count > 0 && !badge) {
                        badge = document.createElement('span');
                        badge.className = button.classList.contains('floating-action') ? 'public-notification-badge' : 'notification-count';
                        badge.dataset.notificationCount = '';
                        button.prepend(badge);
                    }
                    if (badge) {
                        badge.textContent = count;
                        badge.hidden = count === 0;
                    }
                } catch (error) {
                    console.error('Unable to refresh notification count.', error);
                }
            }
        }, 10000);
    }

    const clientMessagesButton = document.getElementById('clientMessagesButton');
    const clientMessagesModal = document.getElementById('clientMessagesModal');
    const clientMessagesFrame = document.getElementById('clientMessagesFrame');
    const closeClientMessages = () => {
        clientMessagesModal?.classList.remove('open');
        clientMessagesModal?.setAttribute('aria-hidden', 'true');
        clientMessagesButton?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('client-messages-open');
    };

    if (clientMessagesButton && clientMessagesModal) {
        clientMessagesButton.addEventListener('click', () => {
            if (clientMessagesModal.classList.contains('open')) {
                closeClientMessages();
                return;
            }

            closeNotificationMenus();
            clientMessagesModal.classList.add('open');
            clientMessagesModal.setAttribute('aria-hidden', 'false');
            clientMessagesButton.setAttribute('aria-expanded', 'true');
            document.body.classList.add('client-messages-open');
            if (clientMessagesFrame && !clientMessagesFrame.src) {
                clientMessagesFrame.src = clientMessagesFrame.dataset.src;
            }
            document.getElementById('closeClientMessages')?.focus();
        });

        document.getElementById('closeClientMessages')?.addEventListener('click', closeClientMessages);
        clientMessagesModal.addEventListener('click', (event) => {
            if (event.target.matches('[data-close-client-messages]')) closeClientMessages();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && clientMessagesModal.classList.contains('open')) {
                closeClientMessages();
                clientMessagesButton.focus();
            }
        });

        window.setInterval(async () => {
            try {
                const response = await fetch(clientMessagesButton.dataset.clientMessageCountUrl, { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const { count } = await response.json();
                let badge = clientMessagesButton.querySelector('[data-client-message-count]');
                if (count > 0 && !badge) {
                    badge = document.createElement('span');
                    badge.className = 'public-notification-badge';
                    badge.dataset.clientMessageCount = '';
                    clientMessagesButton.prepend(badge);
                }
                if (badge) {
                    badge.textContent = count;
                    badge.hidden = count === 0;
                }
            } catch (error) {
                console.error('Unable to refresh message notification count.', error);
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
